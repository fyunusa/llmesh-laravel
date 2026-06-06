<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Commands;

use Illuminate\Console\Command;

/**
 * Artisan command: `php artisan llmesh:providers`
 *
 * Lists all configured LLM providers and their status (installed / missing
 * package, API key present / missing).
 *
 * Usage:
 *   php artisan llmesh:providers
 */
class ProvidersCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llmesh:providers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all configured LLM providers and their availability status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🤖 LLMesh Providers');
        $this->line('');

        $providers = config('llmesh.providers', []);
        $default   = config('llmesh.default', 'openai');

        if (empty($providers)) {
            $this->warn('No providers configured in config/llmesh.php.');
            return self::SUCCESS;
        }

        $rows = [];

        foreach ($providers as $name => $providerConfig) {
            $class  = $providerConfig['class'] ?? null;
            $apiKey = $providerConfig['api_key'] ?? null;
            $model  = $providerConfig['model']   ?? 'N/A';

            $packageStatus = ($class !== null && class_exists($class))
                ? '<fg=green>✓ Installed</>'
                : '<fg=red>✗ Not installed</>';

            $keyStatus = ! empty($apiKey)
                ? '<fg=green>✓ Configured</>'
                : '<fg=yellow>⚠ Missing</>';

            $isDefault = ($name === $default) ? '<fg=cyan>★ default</>' : '';

            $rows[] = [
                $name . ($isDefault !== '' ? " {$isDefault}" : ''),
                $class ?? '—',
                $model,
                $packageStatus,
                $keyStatus,
            ];
        }

        $this->table(
            ['Name', 'Class', 'Default Model', 'Package', 'API Key'],
            $rows,
        );

        $this->line('');
        $this->line("<fg=cyan>Default provider:</> {$default}");
        $this->line("<fg=cyan>Memory driver:</> " . config('llmesh.memory.driver', 'database'));

        return self::SUCCESS;
    }
}
