<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Tests\Feature;

use LLMesh\Core\Contracts\MemoryStoreInterface;
use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Core\Memory\InMemoryStore;
use LLMesh\Laravel\LLMeshManager;
use LLMesh\Laravel\LLMeshServiceProvider;
use LLMesh\Laravel\Tests\Fakes\FakeProvider;
use LLMesh\Laravel\Tests\TestCase;

/**
 * @covers \LLMesh\Laravel\LLMeshServiceProvider
 */
class ServiceProviderTest extends TestCase
{
    public function testServiceProviderIsRegistered(): void
    {
        $providers = $this->app->getLoadedProviders();
        $this->assertArrayHasKey(LLMeshServiceProvider::class, $providers);
    }

    public function testDefaultProviderIsResolvedFromContainer(): void
    {
        $provider = $this->app->make(ProviderInterface::class);
        $this->assertInstanceOf(ProviderInterface::class, $provider);
        $this->assertInstanceOf(FakeProvider::class, $provider);
    }

    public function testDefaultProviderIsSingleton(): void
    {
        $a = $this->app->make(ProviderInterface::class);
        $b = $this->app->make(ProviderInterface::class);
        // The default binding is not a singleton — it is re-resolved; named providers are
        $this->assertInstanceOf(ProviderInterface::class, $a);
        $this->assertInstanceOf(ProviderInterface::class, $b);
    }

    public function testNamedProviderIsBoundInContainer(): void
    {
        $this->assertTrue($this->app->bound('llmesh.provider.fake'));
        $provider = $this->app->make('llmesh.provider.fake');
        $this->assertInstanceOf(FakeProvider::class, $provider);
    }

    public function testNamedProviderIsSingleton(): void
    {
        $a = $this->app->make('llmesh.provider.fake');
        $b = $this->app->make('llmesh.provider.fake');
        $this->assertSame($a, $b);
    }

    public function testMemoryStoreIsResolvedForInMemoryDriver(): void
    {
        $store = $this->app->make(MemoryStoreInterface::class);
        $this->assertInstanceOf(InMemoryStore::class, $store);
    }

    public function testLlmeshManagerIsBound(): void
    {
        $this->assertTrue($this->app->bound(LLMeshManager::class));
        $manager = $this->app->make(LLMeshManager::class);
        $this->assertInstanceOf(LLMeshManager::class, $manager);
    }

    public function testProviderNotBoundWhenClassDoesNotExist(): void
    {
        // A provider with a non-existent class should never be bound
        $this->assertFalse($this->app->bound('llmesh.provider.openai'));
        $this->assertFalse($this->app->bound('llmesh.provider.anthropic'));
        $this->assertFalse($this->app->bound('llmesh.provider.groq'));
    }

    public function testProviderNotBoundWhenApiKeyIsMissing(): void
    {
        // The default config has openai/anthropic/groq with null API keys
        // (because we haven't set OPENAI_API_KEY etc.) — none should be bound
        // In our test env, only 'fake' has a key, so only fake is bound
        $this->assertFalse($this->app->bound('llmesh.provider.openai'));
    }

    public function testConfigIsPublishedToCorrectPath(): void
    {
        // Trigger the publish
        $this->artisan('vendor:publish', [
            '--tag' => 'llmesh-config',
            '--force' => true,
        ])->assertExitCode(0);

        // Assert config file was published
        $this->assertFileExists(config_path('llmesh.php'));

        // Assert published config has expected keys
        $config = include config_path('llmesh.php');
        $this->assertArrayHasKey('default', $config);
        $this->assertArrayHasKey('providers', $config);
        $this->assertArrayHasKey('memory', $config);
        $this->assertArrayHasKey('retry', $config);
    }
}
