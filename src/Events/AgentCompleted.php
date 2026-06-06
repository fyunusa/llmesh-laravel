<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Events;

use LLMesh\Core\Agents\AgentResult;

/**
 * Laravel event fired when a queued `RunAgentJob` completes successfully.
 *
 * Listeners can access the full `AgentResult` (final text, steps, usage)
 * and the session ID that was used for memory persistence.
 *
 * @example
 * ```php
 * // In EventServiceProvider or using #[AsListener]:
 * Event::listen(AgentCompleted::class, function (AgentCompleted $event) {
 *     Log::info("Agent finished for session {$event->sessionId}", [
 *         'text'  => $event->result->finalText,
 *         'steps' => count($event->result->steps),
 *     ]);
 * });
 * ```
 */
final class AgentCompleted
{
    public function __construct(
        /** The full result from the agent loop */
        public readonly AgentResult $result,
        /** The session ID used for memory and correlation */
        public readonly string $sessionId,
    ) {
    }
}
