<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single text chunk from a project document or email, with its embedding vector.
 *
 * Used by ProjectContextSearchService to run cosine similarity search
 * against pgvector when the PM asks a question about a project.
 *
 * @property int         $id
 * @property int|null    $project_document_id
 * @property int|null    $communication_id
 * @property int|null    $project_id
 * @property int         $chunk_index
 * @property string      $chunk_text
 * @property int|null    $token_count
 * @property array|null  $metadata
 * @property string|null $embedding   raw pgvector string, not cast (used in raw SQL)
 */
class DocumentChunk extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'project_document_id',
        'communication_id',
        'project_id',
        'chunk_index',
        'chunk_text',
        'token_count',
        'metadata',
        // 'embedding' is set via raw SQL in the service — not through Eloquent
    ];

    protected $casts = [
        'chunk_index' => 'integer',
        'token_count' => 'integer',
        'metadata'    => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function projectDocument(): BelongsTo
    {
        return $this->belongsTo(ProjectDocument::class, 'project_document_id');
    }

    public function communication(): BelongsTo
    {
        return $this->belongsTo(ProjectCommunication::class, 'communication_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Truncate chunk_text for display (e.g. in search result cards).
     */
    public function excerpt(int $chars = 200): string
    {
        return strlen($this->chunk_text) > $chars
            ? substr($this->chunk_text, 0, $chars) . '…'
            : $this->chunk_text;
    }

    /**
     * Source label for display: filename or email subject.
     */
    public function getSourceLabelAttribute(): string
    {
        if ($this->projectDocument) {
            return $this->projectDocument->original_filename;
        }

        if ($this->communication) {
            return 'Email: ' . ($this->communication->subject ?? '(no subject)');
        }

        return 'Unknown source';
    }
}
