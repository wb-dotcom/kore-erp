<?php

namespace App\Console\Commands;

use App\Models\SystemSetting;
use App\Services\OllamaChatService;
use Illuminate\Console\Command;

/**
 * CLI management for Ollama models in Kore AI.
 *
 * Usage:
 *   php artisan kore:ai:models list               List installed models
 *   php artisan kore:ai:models pull llama3:8b     Pull a new model
 *   php artisan kore:ai:models pull phi3:mini     Pull a lightweight fast model
 *   php artisan kore:ai:models enable llama3      Add to enabled models list
 *   php artisan kore:ai:models disable mistral    Remove from enabled models list
 *   php artisan kore:ai:models default llama3:8b  Set default model
 *   php artisan kore:ai:models status             Check Ollama connectivity
 *
 * Recommended model sizes for architecture firm use:
 *   phi3:mini      — 2.3GB — fastest, simple queries, low VRAM
 *   llama3:8b      — 4.7GB — great balance of speed and quality (default)
 *   mistral:7b     — 4.1GB — strong structured reasoning
 *   deepseek-r1:8b — 4.9GB — excellent analysis and chain-of-thought
 *   llama3:70b     — 40GB  — highest quality, needs M4 Pro or quantised
 */
class KoreAiModels extends Command
{
    protected $signature = 'kore:ai:models
                            {action : list|pull|enable|disable|default|status}
                            {model? : Model name (e.g. llama3:8b, mistral:7b, phi3:mini)}';

    protected $description = 'Manage Ollama models for Kore AI';

    public function __construct(private readonly OllamaChatService $ollama)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        return match ($this->argument('action')) {
            'list'    => $this->listModels(),
            'pull'    => $this->pullModel(),
            'enable'  => $this->enableModel(),
            'disable' => $this->disableModel(),
            'default' => $this->setDefault(),
            'status'  => $this->checkStatus(),
            default   => $this->invalidAction(),
        };
    }

    private function listModels(): int
    {
        $this->info('Checking Ollama…');

        if (! $this->ollama->isAvailable()) {
            $this->error('Ollama is not reachable at: ' . config('services.ollama.host'));
            $this->line('Start it with: <fg=yellow>ollama serve</>');
            return 1;
        }

        $installed = $this->ollama->getInstalledModels();
        $enabled   = $this->ollama->getEnabledModels();
        $default   = SystemSetting::get('ai_default_model', 'llama3');

        if (empty($installed)) {
            $this->warn('No models installed. Pull one with:');
            $this->line('  php artisan kore:ai:models pull llama3:8b');
            return 0;
        }

        $this->line('');
        $this->table(
            ['Model', 'Size', 'Params', 'Quantization', 'Enabled', 'Default'],
            array_map(function ($m) use ($enabled, $default) {
                $isEnabled  = in_array($m['name'], $enabled) ? '<fg=green>✓</>' : '';
                $isDefault  = $m['name'] === $default ? '<fg=yellow>★</>' : '';
                return [
                    $m['name'],
                    $m['size_gb'] . ' GB',
                    $m['params'] ?? '-',
                    $m['quantization'] ?? '-',
                    $isEnabled,
                    $isDefault,
                ];
            }, $installed)
        );

        $this->line('');
        $this->line('Enabled models (available to users): <fg=cyan>' . implode(', ', $enabled) . '</>');
        $this->line('Default model: <fg=yellow>' . $default . '</>');

        return 0;
    }

    private function pullModel(): int
    {
        $modelName = $this->argument('model');

        if (! $modelName) {
            $this->error('Specify a model name: php artisan kore:ai:models pull llama3:8b');
            return 1;
        }

        if (! $this->ollama->isAvailable()) {
            $this->error('Ollama is not reachable. Start it with: ollama serve');
            return 1;
        }

        $this->info("Pulling model: {$modelName}");
        $this->line('This may take several minutes depending on the model size…');
        $this->line('');

        $bar     = null;
        $lastPct = -1;

        $this->ollama->pullModel($modelName, function (string $status, ?int $percent) use (&$bar, &$lastPct) {
            if ($percent !== null && $percent !== $lastPct) {
                if (! $bar) {
                    $bar = $this->output->createProgressBar(100);
                    $bar->start();
                }
                $bar->setProgress($percent);
                $lastPct = $percent;
            } elseif ($percent === null) {
                $this->line('  ' . $status);
            }
        });

        if ($bar) {
            $bar->finish();
            $this->line('');
        }

        $this->info("✓ Model {$modelName} ready.");
        $this->line('Enable it for users with: php artisan kore:ai:models enable ' . $modelName);

        return 0;
    }

    private function enableModel(): int
    {
        $model = $this->requireModel();
        if (! $model) return 1;

        $current  = $this->getEnabledList();
        $baseName = explode(':', $model)[0]; // "llama3:8b" → "llama3"

        if (in_array($model, $current) || in_array($baseName, $current)) {
            $this->warn("Model '{$model}' is already enabled.");
            return 0;
        }

        $current[] = $model;
        SystemSetting::set('ai_enabled_models', implode(',', $current));
        $this->info("✓ {$model} enabled for users.");

        return 0;
    }

    private function disableModel(): int
    {
        $model   = $this->requireModel();
        if (! $model) return 1;

        $current = $this->getEnabledList();
        $updated = array_values(array_filter($current, fn($m) => $m !== $model && !str_starts_with($m, $model)));

        SystemSetting::set('ai_enabled_models', implode(',', $updated));
        $this->info("✓ {$model} disabled.");

        return 0;
    }

    private function setDefault(): int
    {
        $model = $this->requireModel();
        if (! $model) return 1;

        SystemSetting::set('ai_default_model', $model);
        $this->info("✓ Default model set to: {$model}");

        return 0;
    }

    private function checkStatus(): int
    {
        $host      = config('services.ollama.host');
        $available = $this->ollama->isAvailable();

        $this->line('');
        $this->line('<fg=cyan>Kore AI — Ollama Status</>');
        $this->line(str_repeat('─', 40));
        $this->line('Host:      ' . $host);
        $this->line('Status:    ' . ($available ? '<fg=green>● Online</>' : '<fg=red>● Offline</>'));

        if ($available) {
            $models  = $this->ollama->getInstalledModels();
            $enabled = $this->ollama->getEnabledModels();
            $this->line('Models:    ' . count($models) . ' installed, ' . count($enabled) . ' enabled');
            $this->line('Default:   ' . SystemSetting::get('ai_default_model', 'llama3'));
        } else {
            $this->line('');
            $this->warn('Ollama is not running. Start it with: ollama serve');
        }

        $this->line('');

        return $available ? 0 : 1;
    }

    private function invalidAction(): int
    {
        $this->error('Unknown action. Use: list, pull, enable, disable, default, status');
        return 1;
    }

    private function requireModel(): ?string
    {
        $model = $this->argument('model');
        if (! $model) {
            $this->error('Model name required.');
        }
        return $model;
    }

    private function getEnabledList(): array
    {
        return array_filter(
            array_map('trim', explode(',', SystemSetting::get('ai_enabled_models', 'llama3')))
        );
    }
}
