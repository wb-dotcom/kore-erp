<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An inbound email captured by the Postmark webhook.
 *
 * @property int         $id
 * @property int|null    $project_id
 * @property string|null $message_id
 * @property string      $from_email
 * @property string|null $from_name
 * @property string      $to_email
 * @property string|null $subject
 * @property string|null $sent_at
 * @property string|null $body_text
 * @property string|null $body_html
 * @property string|null $extracted_project_number
 * @property string      $processing_status         pending|matched|unmatched|error
 * @property string|null $processing_error
 * @property array       $raw_payload
 */
class ProjectCommunication extends Model
{
    protected $fillable = [
        'project_id',
        'message_id',
        'from_email',
        'from_name',
        'to_email',
        'subject',
        'sent_at',
        'body_text',
        'body_html',
        'extracted_project_number',
        'processing_status',
        'processing_error',
        'raw_payload',
    ];

    protected $casts = [
        'sent_at'     => 'datetime',
        'raw_payload' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class, 'communication_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function isMatched(): bool
    {
        return $this->processing_status === 'matched';
    }

    public function getSenderDisplayAttribute(): string
    {
        return $this->from_name
            ? "{$this->from_name} <{$this->from_email}>"
            : $this->from_email;
    }
}
