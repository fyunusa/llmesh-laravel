<?php

declare(strict_types=1);

namespace LLMesh\Laravel;

use Illuminate\Support\ServiceProvider;
use LLMesh\Core\Contracts\MemoryStoreInterface;
use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Core\LLMesh as CoreLLMesh;
use LLMesh\Core\Memory\InMemoryStore;
use LLMesh\Laravel\Commands\ClearMemoryCommand;
use LLMesh\Laravel\Commands\ProvidersCommand;
use LLMesh\Laravel\Commands\TestCommand;
use LLMesh\Laravel\Memory\EloquentMemoryStore;

/**
 * Laravel Service Provider for the LLMesh package.
 *
 * Handles:
 *  - Merging and publishing configuration
 *  - Registering provider singletons in the container
 *  - Binding `ProviderInterface` to the configured default
 *  - Binding `MemoryStoreInterface` to the configured memory driver
 *  - Publishing database migration
 *  - Registering Artisan commands
 */
class LLMeshServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/llmesh.php',
            'llmesh',
        );

        $this->registerProviders();
        $this->registerDefaultProvider();
        $this->registerMemoryStore();
        $this->registerManager();
    }

    /**
     * Register the LLMeshManager singleton — used by the Facade accessor.
     */
    private function registerManager(): void
    {
        $this->app->singleton(
            \LLMesh\Laravel\LLMeshManager::class,
            fn ($app) => new \LLMesh\Laravel\LLMeshManager(
                $app->make(ProviderInterface::class),
            ),
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->publishConfig();
        $this->publishMigrations();
        $this->registerCommands();

        // Wire the Laravel event dispatcher into LLMesh core so core events
        // are dispatched through Laravel's event system automatically.
        if ($this->app->bound('events')) {
            $coreDispatcher = new \LLMesh\Laravel\Events\LaravelEventDispatcher(
                $this->app->make('events'),
            );
            CoreLLMesh::withEventDispatcher($coreDispatcher);
        }
    }

    // =========================================================================
    // Registration helpers
    // =========================================================================

    /**
     * Register each configured provider as a named lazy singleton.
     *
     * Providers are registered as lazy bindings — config is read at resolution
     * time, not at registration time. This ensures that test environments that
     * modify config (e.g. Testbench `defineEnvironment`) work correctly.
     *
     * A provider is only instantiated when:
     *   1. Its class exists (the provider package is installed), and
     *   2. An API key has been configured.
     */
    private function registerProviders(): void
    {
        // Register a catch-all pattern: use `booted` to register providers
        // after all service providers have registered and config is final.
        $this->app->booted(function () {
            $providers = config('llmesh.providers', []);

            foreach ($providers as $name => $providerConfig) {
                $class  = $providerConfig['class'] ?? null;
                $apiKey = $providerConfig['api_key'] ?? null;

                // Never instantiate a provider whose package is not installed
                if ($class === null || ! class_exists($class)) {
                    continue;
                }

                // Never instantiate a provider whose API key is missing
                if (empty($apiKey)) {
                    continue;
                }

                $this->app->singletonIf(
                    "llmesh.provider.{$name}",
                    static function () use ($class, $providerConfig): ProviderInterface {
                        return new $class(
                            $providerConfig['api_key'],
                            array_merge(
                                ['model' => $providerConfig['model'] ?? null],
                                $providerConfig['options'] ?? [],
                            ),
                        );
                    },
                );
            }
        });
    }

    /**
     * Bind `ProviderInterface` to the configured default provider singleton.
     *
     * If the default provider is not available (package not installed or no
     * API key), the binding is deferred — attempting to resolve it will throw
     * an appropriate container exception.
     */
    private function registerDefaultProvider(): void
    {
        $this->app->bind(
            ProviderInterface::class,
            function ($app) {
                $default = config('llmesh.default', 'openai');
                $key     = "llmesh.provider.{$default}";

                if ($app->bound($key)) {
                    return $app->make($key);
                }

                // Fall back: try to resolve from provider config directly
                $providerConfig = config("llmesh.providers.{$default}", []);
                $class          = $providerConfig['class'] ?? null;
                $apiKey         = $providerConfig['api_key'] ?? null;

                if ($class !== null && class_exists($class) && ! empty($apiKey)) {
                    return new $class(
                        $apiKey,
                        array_merge(
                            ['model' => $providerConfig['model'] ?? null],
                            $providerConfig['options'] ?? [],
                        ),
                    );
                }

                throw new \RuntimeException(
                    "LLMesh: Default provider '{$default}' is not available. "
                    . "Ensure the provider package is installed and the API key is configured.",
                );
            },
        );
    }

    /**
     * Bind `MemoryStoreInterface` based on the configured memory driver.
     */
    private function registerMemoryStore(): void
    {
        $this->app->singleton(
            MemoryStoreInterface::class,
            function ($app) {
                $driver = config('llmesh.memory.driver', 'database');

                return match ($driver) {
                    'database' => new EloquentMemoryStore(),
                    'in_memory' => new InMemoryStore(),
                    'redis' => $this->makeRedisStore($app),
                    default => new EloquentMemoryStore(),
                };
            },
        );
    }

    /**
     * Attempt to create a RedisStore using the configured Laravel Redis connection.
     *
     * Falls back to InMemoryStore when the Redis package is unavailable.
     */
    private function makeRedisStore($app): MemoryStoreInterface
    {
        $connection = config('llmesh.memory.redis.connection', 'default');
        $prefix     = config('llmesh.memory.redis.prefix', 'llmesh:memory:');
        $ttl        = (int) config('llmesh.memory.ttl', 3600);

        // RedisStore requires either ext-redis or predis
        if (! class_exists(\LLMesh\Core\Memory\RedisStore::class)) {
            return new InMemoryStore();
        }

        try {
            $redis = $app->make('redis')->connection($connection)->client();
            return new \LLMesh\Core\Memory\RedisStore($redis, $prefix, $ttl);
        } catch (\Throwable) {
            return new InMemoryStore();
        }
    }

    // =========================================================================
    // Publishing helpers
    // =========================================================================

    private function publishConfig(): void
    {
        $this->publishes(
            [__DIR__ . '/../config/llmesh.php' => config_path('llmesh.php')],
            'llmesh-config',
        );
    }

    private function publishMigrations(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ],
            'llmesh-migrations',
        );
    }

    private function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            TestCommand::class,
            ProvidersCommand::class,
            ClearMemoryCommand::class,
        ]);
    }
}
