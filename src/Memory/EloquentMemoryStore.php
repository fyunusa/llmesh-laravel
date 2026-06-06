<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Memory;

use LLMesh\Core\Contracts\MemoryStoreInterface;
use LLMesh\Core\Exceptions\LLMeshException;

/**
 * Eloquent-backed conversation memory store.
 *
 * Implements `MemoryStoreInterface` using the `LlmeshMemory` Eloquent model,
 * which maps to the `llmesh_memory` database table.
 *
 * Each message is stored as its own row with a monotonically increasing
 * `message_index` scoped to the session. Retrieval always returns messages
 * ordered by `message_index` ascending.
 *
 * @example
 * ```php
 * $store = app(MemoryStoreInterface::class);
 *
 * $store->append('session-123', ['role' => 'user', 'content' => 'Hello!']);
 * $messages = $store->get('session-123'); // [['role' => 'user', 'content' => 'Hello!']]
 * $store->clear('session-123');
 * ```
 */
final class EloquentMemoryStore implements MemoryStoreInterface
{
    /**
     * {@inheritDoc}
     *
     * Computes the next `message_index` for the session and inserts a new row.
     */
    public function append(string $sessionId, array $message): void
    {
        try {
            $nextIndex = LlmeshMemory::where('session_id', $sessionId)->count();

            LlmeshMemory::create([
                'session_id'    => $sessionId,
                'role'          => $message['role'] ?? 'user',
                'content'       => $message['content'] ?? '',
                'metadata'      => $this->extractMetadata($message),
                'message_index' => $nextIndex,
            ]);
        } catch (\Throwable $e) {
            throw new LLMeshException(
                "EloquentMemoryStore: failed to append message for session \"{$sessionId}\": " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * {@inheritDoc}
     *
     * Returns messages ordered by `message_index` ascending.
     */
    public function get(string $sessionId): array
    {
        try {
            return LlmeshMemory::forSession($sessionId)
                ->get()
                ->map(fn (LlmeshMemory $row): array => [
                    'role'       => $row->role,
                    'content'    => $row->content,
                    'toolCallId' => $row->metadata['toolCallId'] ?? null,
                    'toolName'   => $row->metadata['toolName']   ?? null,
                ])
                ->all();
        } catch (\Throwable $e) {
            throw new LLMeshException(
                "EloquentMemoryStore: failed to retrieve messages for session \"{$sessionId}\": " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function clear(string $sessionId): void
    {
        try {
            LlmeshMemory::where('session_id', $sessionId)->delete();
        } catch (\Throwable $e) {
            throw new LLMeshException(
                "EloquentMemoryStore: failed to clear session \"{$sessionId}\": " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $sessionId): bool
    {
        try {
            return LlmeshMemory::where('session_id', $sessionId)->exists();
        } catch (\Throwable $e) {
            throw new LLMeshException(
                "EloquentMemoryStore: failed to check existence of session \"{$sessionId}\": " . $e->getMessage(),
                previous: $e,
            );
        }
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Extract tool-call metadata from a message array.
     *
     * @param  array       $message
     * @return array|null  null if no tool-call metadata present
     */
    private function extractMetadata(array $message): ?array
    {
        $toolCallId = $message['toolCallId'] ?? null;
        $toolName   = $message['toolName']   ?? null;

        if ($toolCallId === null && $toolName === null) {
            return null;
        }

        return array_filter([
            'toolCallId' => $toolCallId,
            'toolName'   => $toolName,
        ]);
    }
}
