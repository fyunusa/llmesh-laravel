<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Jobs;

/**
 * Simple DTO carrying all configuration needed to run the Agent inside a queue job.
 *
 * Designed to be fully serializable (no closures, no resource references) so
 * it can travel across queue backends (database, Redis, SQS, etc.).
 */
final class AgentOptions
{
    /**
     * @param string   $prompt        The user's input prompt
     * @param string   $systemPrompt  System-level instructions for the agent
     * @param string   $providerKey   Key from `llmesh.providers` (e.g. 'openai')
     * @param int      $maxSteps      Maximum agent loop iterations
     * @param array    $toolClasses   Fully-qualified class names of Tool instances to instantiate
     * @param array    $providerOptions Extra options forwarded to the provider
     */
    public function __construct(
        public readonly string $prompt,
        public readonly string $systemPrompt   = '',
        public readonly string $providerKey    = '',
        public readonly int    $maxSteps       = 10,
        public readonly array  $toolClasses    = [],
        public readonly array  $providerOptions = [],
    ) {
    }
}
