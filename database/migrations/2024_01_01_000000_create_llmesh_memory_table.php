<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `llmesh_memory` table for storing conversation history.
 *
 * Publish this migration with:
 *   php artisan vendor:publish --tag=llmesh-migrations
 *
 * Then run:
 *   php artisan migrate
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('llmesh_memory', function (Blueprint $table): void {
            $table->id();

            // Session identifier — all messages for a conversation share this
            $table->string('session_id')->index();

            // Position of this message within the session (0-based)
            $table->unsignedInteger('message_index')->default(0);

            // The LLM role: 'user', 'assistant', 'system', 'tool'
            $table->string('role', 50);

            // The message content (may be long for assistant responses)
            $table->text('content');

            // Optional tool-call metadata (toolCallId, toolName) stored as JSON
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Composite index for efficient session lookups ordered by position
            $table->index(['session_id', 'message_index'], 'llmesh_memory_session_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llmesh_memory');
    }
};
