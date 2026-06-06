<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Event;
use LLMesh\Core\Agents\Agent;
use LLMesh\Core\Contracts\MemoryStoreInterface;
use LLMesh\Core\Contracts\ProviderInterface;
use LLMesh\Laravel\Events\AgentCompleted;
use LLMesh\Laravel\Events\AgentJobFailed;
use LLMesh\Core\Exceptions\LLMeshException;

/**
 * Queue-able job that runs the LLMesh Agent loop in the background.
 *
 * Dispatch this job whenever you want an Agent to run asynchronously.
 * When the agent finishes, an `AgentCompleted` Laravel event is fired so
 * listeners can react (e.g. send a webhook, notify via WebSockets).
 *
 * @example
 * ```php
 * RunAgentJob::dispatch(
 *     new AgentOptions(
 *         prompt:       'Summarise our latest API docs.',
 *         systemPrompt: 'You are a tech writer.',
 *         providerKey:  'openai',
 *         maxSteps:     5,
 *     ),
 *     sessionId: 'user-42-session',
 * )->onQueue('llmesh');
 * ```
 */
class RunAgentJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum number of attempts before the job is marked as failed.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     *
     * @var int
     */
    public int $timeout = 300;

    /**
     * @param AgentOptions $options   All agent configuration
     * @param string       $sessionId Session ID used for memory persistence and event correlation
     */
    public function __construct(
        public readonly AgentOptions $options,
        public readonly string       $sessionId,
    ) {
    }

    /**
     * Execute the job.
     *
     * Resolves the provider and memory store from the container, builds
     * the Agent, runs the loop, and fires `AgentCompleted`.
     */
    public function handle(): void
    {
        try {
            $provider = $this->resolveProvider();
            $memory   = app(MemoryStoreInterface::class);

            // Build tool instances from class names
            $tools = array_map(
                static fn (string $class): object => app($class),
                $this->options->toolClasses,
            );

            $agent = Agent::make(
                provider:     $provider,
                systemPrompt: $this->options->systemPrompt,
                tools:        $tools,
                maxSteps:     $this->options->maxSteps,
            )->withMemory($memory, $this->sessionId);

            $result = $agent->run($this->options->prompt);

            // Fire the completion event so listeners can react
            Event::dispatch(new AgentCompleted($result, $this->sessionId));
        } catch (LLMeshException $e) {
            Event::dispatch(new AgentJobFailed(
                sessionId: $this->sessionId,
                exception: $e,
                agentOptions: (array) $this->options,
            ));
            throw $e;
        } catch (\Throwable $e) {
            Event::dispatch(new AgentJobFailed(
                sessionId: $this->sessionId,
                exception: $e,
                agentOptions: (array) $this->options,
            ));
            throw $e;
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Resolve the provider from the container.
     *
     * When a specific `providerKey` is given in `AgentOptions`, we try to
     * resolve the named singleton `llmesh.provider.{key}`. Otherwise we fall
     * back to the default `ProviderInterface` binding.
     *
     * @throws \RuntimeException When the provider cannot be resolved
     */
    private function resolveProvider(): ProviderInterface
    {
        $key = $this->options->providerKey;

        if ($key !== '') {
            $namedKey = "llmesh.provider.{$key}";
            if (app()->bound($namedKey)) {
                return app($namedKey);
            }
        }

        return app(ProviderInterface::class);
    }
}
