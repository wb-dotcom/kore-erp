<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A file stored on disk, sourced from an email attachment or manual upload.
 *
 * Storage is abstracted via Laravel's filesystem layer.
 * The disk ('local' or 's3') is stored per-record so old documents
 * remain accessible if the default disk changes in future.
 *
 * @property int         $id
 * @property int|null    $project_id
 * @property int|null    $project_phase_id
 * @property int|null    $communication_id
 * @property string      $original_filename
 * @property string      $storage_disk
 * @property string      $storage_path
 * @property string|null $mime_type
 * @property int|null    $file_size_bytes
 * @property string      $document_type
 * @property bool        $is_indexed
 * @property string|null $indexed_at
 */
class ProjectDocument extends Model
{
    protected $fillable = [
        'project_id',
        'project_phase_id',
        'communication_id',
        'original_filename',
        'storage_disk',
        'storage_path',
        'mime_type',
        'file_size_bytes',
        'document_type',
        'is_indexed',
        'indexed_at',
        'created_by',
    ];

    protected $casts = [
        'is_indexed'       => 'boolean',
        'indexed_at'       => 'datetime',
        'file_size_bytes'  => 'integer',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function projectPhase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'project_phase_id');
    }

    public function communication(): BelongsTo
    {
        return $this->belongsTo(ProjectCommunication::class, 'communication_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ── File Access ────────────────────────────────────────────────────────────

    /**
     * Generate a temporary URL valid for 60 minutes (works for both S3 and local).
     * For local disk, falls back to a signed URL via the app's storage proxy.
     */
    public function temporaryUrl(int $minutes = 60): string
    {
        $disk = Storage::disk($this->storage_disk);

        if (method_exists($disk, 'temporaryUrl')) {
            return $disk->temporaryUrl($this->storage_path, now()->addMinutes($minutes));
        }

        // Local fallback: return a signed route URL
        return route('documents.download', ['document' => $this->id]);
    }

    /** Human-readable file size (e.g. "2.4 MB"). */
    public function getFileSizeHumanAttribute(): string
    {
        if (! $this->file_size_bytes) {
            return 'unknown';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = $this->file_size_bytes;
        $i     = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }

    /** True when this document is a PDF, Word doc, or similar text-extractable format. */
    public function isTextExtractable(): bool
    {
        return in_array($this->mime_type, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/html',
        ]);
    }
}
