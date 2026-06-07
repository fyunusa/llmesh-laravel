<?php

/**
 * Bootstrap file for LLMesh Laravel Demos.
 *
 * This file handles Composer autoloading, loads API keys from local .env files,
 * and configures a minimal in-memory Laravel application container.
 */

// 1. Load Composer Autoloader
$autoloader = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoloader)) {
    echo "⚠️  Autoload Error: Please run 'composer install' in the repository root first.\n";
    exit(1);
}
require $autoloader;

use Illuminate\Foundation\Application;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Facade;
use LLMesh\Laravel\LLMeshServiceProvider;

// 2. Simple helper to parse .env file
function loadEnvFile(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$name] = $value;
            putenv("{$name}={$value}");
        }
    }
}

// Load env files from repo root or the lab directory
loadEnvFile(dirname(__DIR__) . '/.env');
loadEnvFile(dirname(dirname(__DIR__)) . '/llmesh-lab/.env');

$openaiApiKey = isset($_ENV['OPENAI_API_KEY']) ? $_ENV['OPENAI_API_KEY'] : getenv('OPENAI_API_KEY');
$anthropicApiKey = isset($_ENV['ANTHROPIC_API_KEY']) ? $_ENV['ANTHROPIC_API_KEY'] : getenv('ANTHROPIC_API_KEY');

if (!$openaiApiKey && !$anthropicApiKey) {
    echo "⚠️  API Key Error: Neither OPENAI_API_KEY nor ANTHROPIC_API_KEY is configured.\n";
    echo "Please set these environment variables or create a .env file in the llmesh-lab folder.\n";
    exit(1);
}

// 3. Initialize the Laravel Application Container
$app = new Application(dirname(__DIR__));

// 4. Register the config Repository and configure LLMesh Laravel settings
$app->singleton('config', function () use ($openaiApiKey, $anthropicApiKey) {
    return new Repository([
        'llmesh' => [
            // Set default based on which keys are available
            'default' => $openaiApiKey ? 'openai' : 'anthropic',

            'providers' => [
                'openai' => [
                    'class'   => \LLMesh\OpenAI\OpenAIProvider::class,
                    'api_key' => $openaiApiKey,
                    'model'   => 'gpt-4o',
                    'options' => [],
                ],
                'anthropic' => [
                    'class'   => \LLMesh\Anthropic\AnthropicProvider::class,
                    'api_key' => $anthropicApiKey,
                    'model'   => 'claude-3-5-sonnet-20241022',
                    'options' => [],
                ],
            ],

            'memory' => [
                'driver' => 'in_memory',
            ],
        ],
    ]);
});

// 5. Bind events service so LLMeshServiceProvider boots without issues
$app->singleton('events', function () {
    return new \Illuminate\Events\Dispatcher();
});

// 6. Register and Boot the Service Provider
$provider = new LLMeshServiceProvider($app);
$provider->register();
$app->boot();

// 7. Bind the application instance to the Facade system
Facade::setFacadeApplication($app);

return $app;
