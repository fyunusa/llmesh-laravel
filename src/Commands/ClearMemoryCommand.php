<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Commands;

use Illuminate\Console\Command;
use LLMesh\Core\Contracts\MemoryStoreInterface;

/**
 * Artisan command: `php artisan llmesh:clear-memory {sessionId}`
 *
 * Clears all stored conversation messages for the given session.
 *
 * Usage:
 *   php artisan llmesh:clear-memory user-42
 *   php artisan llmesh:clear-memory user-42 --confirm
 */
class ClearMemoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'llmesh:clear-memory
        {sessionId : The session ID whose memory should be cleared}
        {--force : Skip the confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear conversation memory for a given session ID';

    /**
     * Execute the console command.
     */
    public function handle(MemoryStoreInterface $memory): int
    {
        $sessionId = (string) $this->argument('sessionId');

        if (! $memory->exists($sessionId)) {
            $this->warn("No memory found for session: {$sessionId}");
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Clear all memory for session \"{$sessionId}\"?")) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        try {
            $memory->clear($sessionId);
            $this->info("✓ Memory cleared for session: {$sessionId}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed to clear memory: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
