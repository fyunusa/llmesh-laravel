<?php

/**
 * LLMesh Laravel Integration Configuration
 *
 * Publish this file with:
 *   php artisan vendor:publish --tag=llmesh-config
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default LLM Provider
    |--------------------------------------------------------------------------
    |
    | The default provider that LLMesh will use when no specific provider is
    | given. This must match one of the keys in the 'providers' array below.
    |
    */

    'default' => env('LLMESH_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | LLM Providers
    |--------------------------------------------------------------------------
    |
    | Configure your LLM providers here. Provider packages must be installed
    | separately (e.g., llmesh/openai, llmesh/anthropic). Each provider is
    | only bound to the container when its package class is available and
    | an API key has been configured.
    |
    */

    'providers' => [

        'openai' => [
            'driver'  => 'openai',
            'class'   => \LLMesh\OpenAI\OpenAIProvider::class,
            'api_key' => env('OPENAI_API_KEY'),
            'model'   => env('OPENAI_MODEL', 'gpt-4o'),
            'options' => [],
        ],

        'anthropic' => [
            'driver'  => 'anthropic',
            'class'   => \LLMesh\Anthropic\AnthropicProvider::class,
            'api_key' => env('ANTHROPIC_API_KEY'),
            'model'   => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
            'options' => [],
        ],

        'groq' => [
            'driver'  => 'groq',
            'class'   => \LLMesh\Groq\GroqProvider::class,
            'api_key' => env('GROQ_API_KEY'),
            'model'   => env('GROQ_MODEL', 'llama3-8b-8192'),
            'options' => [],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Conversation Memory
    |--------------------------------------------------------------------------
    |
    | Configure the memory driver used to store conversation history.
    |
    | Supported drivers:
    |   - "database"   — stores messages in the `llmesh_memory` table via Eloquent
    |   - "redis"      — stores messages in Redis (requires llmesh/core RedisStore)
    |   - "in_memory"  — ephemeral storage within the current PHP process
    |
    */

    'memory' => [
        'driver' => env('LLMESH_MEMORY_DRIVER', 'database'),
        'ttl'    => (int) env('LLMESH_MEMORY_TTL', 3600),
        'table'  => env('LLMESH_MEMORY_TABLE', 'llmesh_memory'),
        'redis'  => [
            'connection' => env('LLMESH_REDIS_CONNECTION', 'default'),
            'prefix'     => env('LLMESH_REDIS_PREFIX', 'llmesh:memory:'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Number of retry attempts and delay between retries (in milliseconds)
    | when a provider request fails due to transient errors.
    |
    */

    'retry' => [
        'attempts' => (int) env('LLMESH_RETRY_ATTEMPTS', 3),
        'delay_ms' => (int) env('LLMESH_RETRY_DELAY_MS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Settings
    |--------------------------------------------------------------------------
    |
    | Default queue connection and queue name for RunAgentJob.
    |
    */

    'queue' => [
        'connection' => env('LLMESH_QUEUE_CONNECTION', null), // null = default connection
        'queue'      => env('LLMESH_QUEUE_NAME', 'default'),
    ],

];
