<?php

declare(strict_types=1);

namespace LLMesh\Laravel\Memory;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the `llmesh_memory` table.
 *
 * Represents a single message within a conversation session.
 *
 * @property int         $id
 * @property string      $session_id
 * @property string      $role
 * @property string      $content
 * @property array|null  $metadata
 * @property int         $message_index
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @method static Builder forSession(string $sessionId)
 */
class LlmeshMemory extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'llmesh_memory';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'session_id',
        'role',
        'content',
        'metadata',
        'message_index',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata'      => 'array',
        'message_index' => 'integer',
    ];

    /**
     * Scope: filter messages by session ID, ordered by index.
     */
    public function scopeForSession(Builder $query, string $sessionId): Builder
    {
        return $query
            ->where('session_id', $sessionId)
            ->orderBy('message_index');
    }
}
