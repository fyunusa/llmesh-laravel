<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Tests\Unit;

use LLMesh\Laravel\Memory\EloquentMemoryStore;
use LLMesh\Laravel\Tests\TestCase;
use Orchestra\Testbench\Concerns\WithWorkbench;

/**
 * @covers \LLMesh\Laravel\Memory\EloquentMemoryStore
 * @covers \LLMesh\Laravel\Memory\LlmeshMemory
 */
class EloquentMemoryStoreTest extends TestCase
{
    /**
     * Set up an in-memory SQLite database with the llmesh_memory table.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Override memory driver to database for this test class
        config(['llmesh.memory.driver' => 'database']);

        // Run only the llmesh migration
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
    }

    private function makeStore(): EloquentMemoryStore
    {
        return new EloquentMemoryStore();
    }

    public function testAppendStoresMessage(): void
    {
        $store = $this->makeStore();

        $store->append('session-1', [
            'role'    => 'user',
            'content' => 'Hello!',
        ]);

        $messages = $store->get('session-1');

        $this->assertCount(1, $messages);
        $this->assertSame('user',   $messages[0]['role']);
        $this->assertSame('Hello!', $messages[0]['content']);
    }

    public function testMessagesReturnedInOrder(): void
    {
        $store = $this->makeStore();

        $store->append('session-2', ['role' => 'user',      'content' => 'First']);
        $store->append('session-2', ['role' => 'assistant', 'content' => 'Second']);
        $store->append('session-2', ['role' => 'user',      'content' => 'Third']);

        $messages = $store->get('session-2');

        $this->assertCount(3, $messages);
        $this->assertSame('First',  $messages[0]['content']);
        $this->assertSame('Second', $messages[1]['content']);
        $this->assertSame('Third',  $messages[2]['content']);
    }

    public function testGetReturnsEmptyArrayForUnknownSession(): void
    {
        $store = $this->makeStore();

        $messages = $store->get('unknown-session-xyz');
        $this->assertSame([], $messages);
    }

    public function testExistsReturnsTrueAfterAppend(): void
    {
        $store = $this->makeStore();

        $this->assertFalse($store->exists('session-3'));

        $store->append('session-3', ['role' => 'user', 'content' => 'Hi']);

        $this->assertTrue($store->exists('session-3'));
    }

    public function testClearRemovesAllMessagesForSession(): void
    {
        $store = $this->makeStore();

        $store->append('session-4', ['role' => 'user',      'content' => 'A']);
        $store->append('session-4', ['role' => 'assistant', 'content' => 'B']);

        $this->assertTrue($store->exists('session-4'));
        $this->assertCount(2, $store->get('session-4'));

        $store->clear('session-4');

        $this->assertFalse($store->exists('session-4'));
        $this->assertSame([], $store->get('session-4'));
    }

    public function testClearDoesNotAffectOtherSessions(): void
    {
        $store = $this->makeStore();

        $store->append('session-5a', ['role' => 'user', 'content' => 'Keep me']);
        $store->append('session-5b', ['role' => 'user', 'content' => 'Clear me']);

        $store->clear('session-5b');

        $this->assertCount(1, $store->get('session-5a'));
        $this->assertSame([], $store->get('session-5b'));
    }

    public function testToolCallMetadataRoundTrip(): void
    {
        $store = $this->makeStore();

        $store->append('session-tool', [
            'role'       => 'tool',
            'content'    => '{"weather":"sunny"}',
            'toolCallId' => 'call-abc',
            'toolName'   => 'get_weather',
        ]);

        $messages = $store->get('session-tool');

        $this->assertCount(1, $messages);
        $this->assertSame('call-abc',    $messages[0]['toolCallId']);
        $this->assertSame('get_weather', $messages[0]['toolName']);
    }

    public function testAppendGeneratesMonotonicMessageIndex(): void
    {
        $store = $this->makeStore();

        for ($i = 0; $i < 5; $i++) {
            $store->append('session-idx', ['role' => 'user', 'content' => "Message {$i}"]);
        }

        $messages = $store->get('session-idx');

        $this->assertCount(5, $messages);
        $this->assertSame('Message 0', $messages[0]['content']);
        $this->assertSame('Message 4', $messages[4]['content']);
    }

    public function testNullToolCallMetadataIsHandledGracefully(): void
    {
        $store = $this->makeStore();

        $store->append('session-null-meta', [
            'role'    => 'user',
            'content' => 'Just a message',
        ]);

        $messages = $store->get('session-null-meta');

        $this->assertCount(1, $messages);
        $this->assertNull($messages[0]['toolCallId']);
        $this->assertNull($messages[0]['toolName']);
    }
}
