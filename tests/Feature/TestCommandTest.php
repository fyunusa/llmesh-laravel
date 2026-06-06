<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Tests\Feature;

use LLMesh\Laravel\Tests\TestCase;

/**
 * @covers \LLMesh\Laravel\Commands\TestCommand
 */
class TestCommandTest extends TestCase
{
    public function testCommandOutputsResponseText(): void
    {
        $this->artisan('llmesh:test')
            ->assertExitCode(0)
            ->expectsOutputToContain('LLMesh Test')
            ->expectsOutputToContain('Fake response from test provider.');
    }

    public function testCommandAcceptsCustomPrompt(): void
    {
        $this->artisan('llmesh:test', ['--prompt' => 'What is 2+2?'])
            ->assertExitCode(0)
            ->expectsOutputToContain('What is 2+2?');
    }

    public function testCommandFailsWhenProviderClassMissing(): void
    {
        // Override the default to a non-existent provider
        config(['llmesh.default' => 'nonexistent']);
        config(['llmesh.providers.nonexistent' => [
            'class'   => 'NonExistent\\Provider\\Class',
            'api_key' => 'some-key',
            'model'   => 'none',
            'options' => [],
        ]]);

        $this->artisan('llmesh:test')
            ->assertExitCode(1);
    }

    public function testCommandFailsWhenApiKeyMissing(): void
    {
        config(['llmesh.default' => 'nokeytest']);
        config(['llmesh.providers.nokeytest' => [
            'class'   => \LLMesh\Laravel\Tests\Fakes\FakeProvider::class,
            'api_key' => null,
            'model'   => 'any',
            'options' => [],
        ]]);

        $this->artisan('llmesh:test')
            ->assertExitCode(1);
    }

    public function testProvidersCommandListsProviders(): void
    {
        $this->artisan('llmesh:providers')
            ->assertExitCode(0)
            ->expectsOutputToContain('LLMesh Providers')
            ->expectsOutputToContain('fake');
    }

    public function testClearMemoryCommandHandlesMissingSession(): void
    {
        $this->artisan('llmesh:clear-memory', ['sessionId' => 'no-such-session'])
            ->assertExitCode(0)
            ->expectsOutputToContain('No memory found');
    }
}
