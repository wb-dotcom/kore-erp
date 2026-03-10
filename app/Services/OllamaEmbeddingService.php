<?php

namespace App\Services;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates text embeddings using a locally-running Ollama instance.
 *
 * Ollama runs on the Mac M4 host; the Docker container reaches it via the
 * host gateway alias configured in docker-compose.yml.
 *
 * API: POST {OLLAMA_HOST}/api/embeddings
 *   Request:  { "model": "nomic-embed-text", "prompt": "text to embed" }
 *   Response: { "embedding": [0.123, 0.456, ...] }   // 768 floats for nomic-embed-text
 *
 * Install the model on the host:
 *   ollama pull nomic-embed-text
 *
 * Dimension: 768 — must match document_chunks.embedding vector(768)
 */
class OllamaEmbeddingService
{
    private string $host;
    private string $model;

    public function __construct()
    {
        $this->host  = rtrim(config('services.ollama.host', 'http://host.docker.internal:11434'), '/');
        $this->model = config('services.ollama.embed_model', 'nomic-embed-text');
    }

    /**
     * Embed a single text string.
     *
     * @return float[]  768-dimensional vector
     * @throws \RuntimeException on API failure
     */
    public function embed(string $text): array
    {
        $text = $this->sanitise($text);

        try {
            $response = Http::timeout(60)
                ->retry(3, 1000)
                ->post("{$this->host}/api/embeddings", [
                    'model'  => $this->model,
                    'prompt' => $text,
                ]);

            if ($response->failed()) {
                throw new \RuntimeException(
                    "Ollama embedding API error [{$response->status()}]: {$response->body()}"
                );
            }

            $vector = $response->json('embedding');

            if (! is_array($vector) || empty($vector)) {
                throw new \RuntimeException('Ollama returned an empty embedding vector.');
            }

            return array_map('floatval', $vector);

        } catch (RequestException $e) {
            throw new \RuntimeException("Ollama connection failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Embed a batch of texts. Returns an array of vectors in the same order.
     * Ollama does not currently support native batch embedding, so we call
     * embed() in sequence. Parallelism can be added here if Ollama gains batch support.
     *
     * @param  string[]  $texts
     * @return float[][]
     */
    public function embedBatch(array $texts): array
    {
        $vectors = [];

        foreach ($texts as $i => $text) {
            try {
                $vectors[] = $this->embed($text);
            } catch (\RuntimeException $e) {
                Log::warning("OllamaEmbeddingService: failed to embed chunk {$i}", [
                    'error'   => $e->getMessage(),
                    'preview' => substr($text, 0, 100),
                ]);
                // Store a zero vector so the chunk row can still be saved (un-searchable)
                $vectors[] = array_fill(0, 768, 0.0);
            }
        }

        return $vectors;
    }

    /**
     * Format a float vector as pgvector's literal string: [0.1,0.2,...]
     * Safe to interpolate into raw SQL via DB::statement with parameter binding.
     */
    public static function toPgVectorLiteral(array $vector): string
    {
        return '[' . implode(',', $vector) . ']';
    }

    // ── Private ────────────────────────────────────────────────────────────────

    private function sanitise(string $text): string
    {
        // Remove null bytes and excessive whitespace
        $text = str_replace("\0", '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
}
