<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Commands;

use Illuminate\Console\Command;
use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Core\Generators\GenerateTextOptions;
use LLMesh\Core\LLMesh;

/**
 * Artisan command: `php artisan llmesh:test`
 *
 * Sends a test prompt to the configured default provider and prints the
 * response. Useful for verifying that the provider is correctly configured.
 *
 * Usage:
 *   php artisan llmesh:test
 *   php artisan llmesh:test --prompt="What is PHP?"
 *   php artisan llmesh:test --provider=anthropic
 */
class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llmesh:test
        {--prompt=Hello! Please confirm you are working correctly. : The test prompt to send}
        {--provider= : Override the default provider (must match a key in llmesh.providers)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test prompt to the configured LLM provider and display the response';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $prompt   = (string) $this->option('prompt');
        $override = $this->option('provider');

        $this->info('🤖 LLMesh Test');
        $this->line('');

        // Resolve the provider
        try {
            if ($override !== null) {
                $provider = $this->resolveNamedProvider((string) $override);
            } else {
                $provider = app(ProviderInterface::class);
            }
        } catch (\Throwable $e) {
            $this->error('Provider resolution failed: ' . $e->getMessage());
            $this->line('');
            $this->warn('Make sure your provider package is installed and API key is configured.');
            return self::FAILURE;
        }

        $providerName = $override ?? config('llmesh.default', 'openai');

        $this->line("<fg=cyan>Provider:</> {$providerName}");
        $this->line("<fg=cyan>Prompt:</> {$prompt}");
        $this->line('');

        $this->line('Sending request…');

        try {
            $options  = GenerateTextOptions::make()->withPrompt($prompt);
            $response = LLMesh::generateText($provider, $options);

            $this->line('');
            $this->line('<fg=green>✓ Response received</>');
            $this->line('');
            $this->line('<fg=yellow>Response:</>');
            $this->line($response->getText());
            $this->line('');

            $usage = $response->getUsage();
            $this->line('<fg=gray>Usage: '
                . $usage->getInputTokens() . ' input tokens, '
                . $usage->getOutputTokens() . ' output tokens</>');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Request failed: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Resolve a named provider from config, checking class existence and API key.
     *
     * @throws \RuntimeException When the provider is unavailable
     */
    private function resolveNamedProvider(string $name): ProviderInterface
    {
        $key = "llmesh.provider.{$name}";

        if (app()->bound($key)) {
            return app($key);
        }

        $providerConfig = config("llmesh.providers.{$name}");

        if ($providerConfig === null) {
            throw new \RuntimeException("Provider '{$name}' is not defined in llmesh config.");
        }

        $class  = $providerConfig['class'] ?? null;
        $apiKey = $providerConfig['api_key'] ?? null;

        if ($class === null || ! class_exists($class)) {
            throw new \RuntimeException(
                "Provider '{$name}' class '{$class}' is not installed. "
                . "Install the corresponding llmesh provider package.",
            );
        }

        if (empty($apiKey)) {
            throw new \RuntimeException(
                "Provider '{$name}' has no API key configured. "
                . "Set the relevant environment variable.",
            );
        }

        return new $class(
            $apiKey,
            array_merge(
                ['model' => $providerConfig['model'] ?? null],
                $providerConfig['options'] ?? [],
            ),
        );
    }
}
