<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'model',
        'sources',
        'processing_time_ms',
        'token_count',
    ];

    protected $casts = [
        'sources'            => 'array',
        'processing_time_ms' => 'integer',
        'token_count'        => 'integer',
        'created_at'         => 'datetime',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(AiConversation::class, 'conversation_id');
    }

    public function isUser(): bool      { return $this->role === 'user'; }
    public function isAssistant(): bool { return $this->role === 'assistant'; }
}
