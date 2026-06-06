<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Tests\Unit\Jobs;

use LLMesh\Laravel\Jobs\RunAgentJob;
use LLMesh\Laravel\Jobs\AgentOptions;
use LLMesh\Laravel\Events\AgentCompleted;
use LLMesh\Laravel\Events\AgentJobFailed;
use LLMesh\Core\Exceptions\ProviderException;
use LLMesh\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Event;

/**
 * @covers \LLMesh\Laravel\Jobs\RunAgentJob
 * @covers \LLMesh\Laravel\Events\AgentJobFailed
 * @covers \LLMesh\Laravel\Events\AgentCompleted
 */
class RunAgentJobTest extends TestCase
{
    public function testJobDispatchesAgentCompletedOnSuccess(): void
    {
        Event::fake([AgentCompleted::class, AgentJobFailed::class]);

        $job = new RunAgentJob(
            options: new AgentOptions(prompt: 'Hello'),
            sessionId: 'test-session',
        );

        $job->handle();

        Event::assertDispatched(AgentCompleted::class, function (AgentCompleted $event) {
            return $event->sessionId === 'test-session' && $event->result->finalText !== '';
        });
        Event::assertNotDispatched(AgentJobFailed::class);
    }

    public function testJobDispatchesAgentJobFailedOnLLMeshException(): void
    {
        Event::fake([AgentCompleted::class, AgentJobFailed::class]);

        $mockProvider = $this->createMock(\LLMesh\Core\Contracts\ProviderInterface::class);
        $mockProvider->method('chat')
            ->willThrowException(new ProviderException('Test API error', 'fake'));

        // Bind the mock provider to the container
        $this->app->instance(\LLMesh\Core\Contracts\ProviderInterface::class, $mockProvider);

        $job = new RunAgentJob(
            options: new AgentOptions(prompt: 'Hello'),
            sessionId: 'test-session',
        );

        $this->expectException(ProviderException::class);

        try {
            $job->handle();
        } finally {
            Event::assertDispatched(AgentJobFailed::class, function (AgentJobFailed $event) {
                return $event->sessionId === 'test-session'
                    && $event->exception instanceof ProviderException
                    && $event->agentOptions['prompt'] === 'Hello';
            });
            Event::assertNotDispatched(AgentCompleted::class);
        }
    }

    public function testJobImplementsShouldQueue(): void
    {
        $job = new RunAgentJob(
            options: new AgentOptions(prompt: 'Hello'),
            sessionId: 'test-session',
        );

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $job);
    }
}
