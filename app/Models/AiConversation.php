<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'model',
        'context_project_id',
        'message_count',
    ];

    protected $casts = [
        'message_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contextProject(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'context_project_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id')->orderBy('created_at');
    }

    /** Returns the last N assistant+user turns as a flat messages array for Ollama. */
    public function recentHistory(int $turns = 10): array
    {
        return $this->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->latest('created_at')
            ->limit($turns * 2)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray();
    }

    /** Increment the message count and auto-title from first user message. */
    public function recordTurn(string $firstUserMessage = ''): void
    {
        $updates = ['message_count' => $this->message_count + 1];

        if (! $this->title && $firstUserMessage) {
            $updates['title'] = mb_substr($firstUserMessage, 0, 80);
        }

        $this->update($updates);
    }
}
