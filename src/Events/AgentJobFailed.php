<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class AgentJobFailed
{
    use Dispatchable;

    /**
     * @param string     $sessionId    The session ID associated with the agent run
     * @param \Throwable $exception    The exception that caused the job to fail
     * @param array      $agentOptions Serialized options passed to the agent job
     */
    public function __construct(
        public readonly string     $sessionId,
        public readonly \Throwable $exception,
        public readonly array      $agentOptions,
    ) {
    }
}
