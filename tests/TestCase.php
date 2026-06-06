<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Tests;

use LLMesh\Laravel\LLMeshServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Base test case for llmesh/laravel feature tests.
 *
 * Bootstraps Orchestra Testbench with the LLMeshServiceProvider and
 * configures a minimal llmesh config pointing at a mock provider.
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get package service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            LLMeshServiceProvider::class,
        ];
    }

    /**
     * Get package aliases.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'LLMesh' => \LLMesh\Laravel\Facades\LLMesh::class,
        ];
    }

    /**
     * Define environment setup for all tests.
     *
     * Uses an in-memory SQLite database so migrations can run without
     * a real database connection.
     *
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        // Use SQLite in-memory for all DB-dependent tests
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Set a fake default provider so the service provider can bind
        // ProviderInterface without needing a real package
        $app['config']->set('llmesh.default', 'fake');
        $app['config']->set('llmesh.providers.fake', [
            'driver'  => 'fake',
            'class'   => \LLMesh\Laravel\Tests\Fakes\FakeProvider::class,
            'api_key' => 'test-key',
            'model'   => 'test-model',
            'options' => [],
        ]);

        // Use in_memory driver so tests don't need the migrations table
        $app['config']->set('llmesh.memory.driver', 'in_memory');
    }
}
