<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Ollama LLM gateway for Kore AI.
 *
 * Supports multiple models: anything installed in Ollama is usable.
 * Common architecture-firm assistant models:
 *   llama3:8b       — fast, good general reasoning (default)
 *   llama3:70b      — best quality, slower (needs 40GB+ VRAM or quantised)
 *   mistral:7b      — strong reasoning, efficient
 *   mixtral:8x7b    — very capable MoE model
 *   phi3:mini       — extremely fast for simple queries
 *   phi3:medium     — strong at structured analysis
 *   deepseek-r1:8b  — excellent reasoning and chain-of-thought
 *   codellama:7b    — for code-related queries
 *
 * Install a model:   ollama pull llama3:8b
 * List models:       ollama list
 * Ollama runs on the Mac M4 host; container reaches it via host gateway:
 *   OLLAMA_HOST=http://host.docker.internal:11434
 *
 * To run Ollama in Docker instead, add to docker-compose.yml:
 *   ollama:
 *     image: ollama/ollama
 *     volumes: [ollama:/root/.ollama]
 *     ports: ["11434:11434"]
 * Then set: OLLAMA_HOST=http://ollama:11434
 *
 * API reference: https://github.com/ollama/ollama/blob/main/docs/api.md
 */
class OllamaChatService
{
    private string $host;

    public function __construct()
    {
        $this->host = rtrim(config('services.ollama.host', 'http://host.docker.internal:11434'), '/');
    }

    // ── Health & Model Discovery ───────────────────────────────────────────────

    /**
     * Check if Ollama is reachable.
     */
    public function isAvailable(): bool
    {
        try {
            $response = Http::timeout(3)->get("{$this->host}/api/tags");
            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * List all models currently installed in Ollama.
     *
     * @return array<array{name: string, size: int, modified_at: string, details: array}>
     */
    public function getInstalledModels(): array
    {
        $response = Http::timeout(10)->get("{$this->host}/api/tags");

        if ($response->failed()) {
            throw new \RuntimeException("Ollama /api/tags failed [{$response->status()}]");
        }

        $models = $response->json('models', []);

        return array_map(function (array $m) {
            return [
                'name'        => $m['name'],
                'size_gb'     => round(($m['size'] ?? 0) / 1_073_741_824, 1),
                'modified_at' => $m['modified_at'] ?? null,
                'family'      => $m['details']['family'] ?? 'unknown',
                'params'      => $m['details']['parameter_size'] ?? null,
                'quantization'=> $m['details']['quantization_level'] ?? null,
            ];
        }, $models);
    }

    /**
     * Get models enabled for users (configured in system settings).
     * Intersected with what's actually installed in Ollama.
     */
    public function getEnabledModels(): array
    {
        $enabledNames = array_filter(
            array_map('trim', explode(',', \App\Models\SystemSetting::get('ai_enabled_models', 'llama3')))
        );

        try {
            $installed = collect($this->getInstalledModels())->pluck('name')->toArray();

            // Match enabled names against installed (allow partial match: "llama3" matches "llama3:8b")
            $available = array_filter($enabledNames, function (string $enabled) use ($installed) {
                foreach ($installed as $inst) {
                    if (str_starts_with($inst, $enabled) || $inst === $enabled) {
                        return true;
                    }
                }
                return false;
            });

            return array_values($available);
        } catch (\Throwable) {
            // Ollama unavailable — return configured names anyway so UI shows something
            return array_values($enabledNames);
        }
    }

    // ── Chat ───────────────────────────────────────────────────────────────────

    /**
     * Non-streaming chat. Returns full response text.
     *
     * @param  array<array{role: string, content: string}>  $messages
     */
    public function chat(array $messages, string $model, array $options = []): string
    {
        $payload = $this->buildPayload($messages, $model, $options, stream: false);

        $response = Http::timeout(120)
            ->retry(2, 1000)
            ->post("{$this->host}/api/chat", $payload);

        if ($response->failed()) {
            throw new \RuntimeException("Ollama chat failed [{$response->status()}]: {$response->body()}");
        }

        return $response->json('message.content', '');
    }

    /**
     * Streaming chat. Calls $onChunk with each text piece as it arrives.
     * Returns the complete assembled response when finished.
     *
     * $onChunk signature: function(string $chunk): void
     * $onDone  signature: function(array $stats): void   (token_count, eval_duration_ms)
     *
     * Uses Guzzle streaming to process newline-delimited JSON from Ollama.
     */
    public function stream(
        array $messages,
        string $model,
        callable $onChunk,
        callable $onDone = null,
        array $options = []
    ): string {
        $payload  = $this->buildPayload($messages, $model, $options, stream: true);
        $full     = '';
        $stats    = [];

        $client   = new \GuzzleHttp\Client(['timeout' => 300]);

        try {
            $response = $client->post("{$this->host}/api/chat", [
                'json'   => $payload,
                'stream' => true,
            ]);

            $body = $response->getBody();

            while (! $body->eof()) {
                $line = $this->readLine($body);

                if ($line === '') {
                    continue;
                }

                $data = json_decode($line, true);

                if (! is_array($data)) {
                    continue;
                }

                $chunk = $data['message']['content'] ?? '';

                if ($chunk !== '') {
                    $full .= $chunk;
                    $onChunk($chunk);
                }

                if ($data['done'] ?? false) {
                    $stats = [
                        'token_count'      => $data['eval_count'] ?? null,
                        'eval_duration_ms' => isset($data['eval_duration'])
                            ? (int) ($data['eval_duration'] / 1_000_000)
                            : null,
                    ];

                    if ($onDone) {
                        $onDone($stats);
                    }
                    break;
                }
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \RuntimeException("Ollama streaming failed: {$e->getMessage()}", 0, $e);
        }

        return $full;
    }

    // ── Model Management ───────────────────────────────────────────────────────

    /**
     * Pull a model from the Ollama registry. Streams progress to $onProgress.
     * $onProgress signature: function(string $status, int|null $percent): void
     */
    public function pullModel(string $modelName, callable $onProgress = null): void
    {
        $client = new \GuzzleHttp\Client(['timeout' => 3600]);

        $response = $client->post("{$this->host}/api/pull", [
            'json'   => ['name' => $modelName, 'stream' => true],
            'stream' => true,
        ]);

        $body = $response->getBody();

        while (! $body->eof()) {
            $line = $this->readLine($body);
            if ($line === '') continue;

            $data = json_decode($line, true);
            if (! is_array($data)) continue;

            $status  = $data['status'] ?? '';
            $total   = $data['total'] ?? 0;
            $completed = $data['completed'] ?? 0;
            $percent = ($total > 0) ? (int) (($completed / $total) * 100) : null;

            if ($onProgress) {
                $onProgress($status, $percent);
            }

            if ($status === 'success') {
                break;
            }
        }
    }

    /**
     * Delete a model from the local Ollama store.
     */
    public function deleteModel(string $modelName): void
    {
        $client = new \GuzzleHttp\Client(['timeout' => 30]);
        $client->delete("{$this->host}/api/delete", ['json' => ['name' => $modelName]]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function buildPayload(
        array $messages,
        string $model,
        array $options,
        bool $stream
    ): array {
        $defaults = [
            'temperature' => (float) \App\Models\SystemSetting::get('ai_temperature', '0.7'),
            'num_ctx'     => (int)   \App\Models\SystemSetting::get('ai_context_window', '4096'),
        ];

        return [
            'model'    => $model,
            'messages' => $messages,
            'stream'   => $stream,
            'options'  => array_merge($defaults, $options),
        ];
    }

    /** Read a single newline-terminated line from a Guzzle StreamInterface. */
    private function readLine(\Psr\Http\Message\StreamInterface $body): string
    {
        $line = '';

        while (! $body->eof()) {
            $char = $body->read(1);
            if ($char === "\n") break;
            $line .= $char;
        }

        return trim($line);
    }
}
