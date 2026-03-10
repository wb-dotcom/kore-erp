<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectCommunication;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Parses a Postmark inbound email payload and persists it to the database.
 *
 * Called from the ProcessInboundEmail queued job — never directly from the controller.
 *
 * Postmark inbound JSON shape (relevant fields):
 * {
 *   "MessageID": "abc-123",
 *   "From":      "sender@example.com",
 *   "FromName":  "John Doe",
 *   "To":        "inbound@yourdomain.postmarkapp.com",
 *   "Subject":   "RE: Project 2025-001 — Schematic Design Review",
 *   "Date":      "Thu, 5 Dec 2024 10:30:00 -0400",
 *   "TextBody":  "...",
 *   "HtmlBody":  "<html>...",
 *   "Attachments": [
 *     { "Name": "plans.pdf", "Content": "<base64>", "ContentType": "application/pdf", "ContentLength": 123456 }
 *   ]
 * }
 */
class InboundEmailService
{
    /**
     * Process a raw Postmark inbound payload array.
     *
     * Returns the created ProjectCommunication record.
     */
    public function process(array $payload): ProjectCommunication
    {
        $subject         = $payload['Subject'] ?? '';
        $projectNumber   = $this->extractProjectNumber($subject);
        $project         = $projectNumber ? $this->findProject($projectNumber) : null;

        $communication = ProjectCommunication::create([
            'project_id'               => $project?->id,
            'message_id'               => $payload['MessageID'] ?? null,
            'from_email'               => $payload['From'] ?? '',
            'from_name'                => $payload['FromName'] ?? null,
            'to_email'                 => $payload['To'] ?? '',
            'subject'                  => $subject,
            'sent_at'                  => $this->parseDate($payload['Date'] ?? null),
            'body_text'                => $payload['TextBody'] ?? null,
            'body_html'                => $payload['HtmlBody'] ?? null,
            'extracted_project_number' => $projectNumber,
            'processing_status'        => $project ? 'matched' : 'unmatched',
            'raw_payload'              => $payload,
        ]);

        if ($project) {
            Log::info("InboundEmail: matched project {$project->project_number}", [
                'communication_id' => $communication->id,
                'from'             => $communication->from_email,
                'subject'          => $subject,
            ]);
        } else {
            Log::warning('InboundEmail: no project match', [
                'communication_id'        => $communication->id,
                'extracted_project_number'=> $projectNumber,
                'subject'                 => $subject,
            ]);
        }

        // Process attachments
        if (! empty($payload['Attachments'])) {
            foreach ($payload['Attachments'] as $attachment) {
                $this->storeAttachment($attachment, $communication, $project);
            }
        }

        return $communication;
    }

    // ── Project Number Extraction ──────────────────────────────────────────────

    /**
     * Extract a project_number string from an email subject line.
     *
     * Matches the firm's project_number format: YYYY-NNN (e.g. 2025-001, 2024-0042).
     *
     * Patterns caught:
     *   "RE: Project 2025-001 – Title"
     *   "FW: 2025-001 Site Review"
     *   "#2025-001"
     *   "Ref: 2025-001"
     *   "Project No. 2025-001"
     *
     * If multiple candidates are found, we query the DB for the first actual match.
     */
    public function extractProjectNumber(string $subject): ?string
    {
        // Extract all YYYY-NNN style tokens from the subject
        preg_match_all('/\b(\d{4}-\d{2,6})\b/', $subject, $matches);

        if (empty($matches[1])) {
            return null;
        }

        // Return the first token that actually matches a project in the DB.
        foreach ($matches[1] as $candidate) {
            if (Project::where('project_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        // If none matched the DB, return the first extracted candidate
        // so it can be stored for manual triage.
        return $matches[1][0];
    }

    // ── Attachment Storage ─────────────────────────────────────────────────────

    /**
     * Decode a base64 Postmark attachment and write it to the documents disk.
     *
     * Storage path convention:
     *   projects/{project_number_or_unmatched}/{year-month}/{uuid}_{filename}
     *
     * The disk is configured via DOCUMENTS_DISK env var (local or s3).
     * Switching to S3 requires only the env var change — no code change.
     */
    private function storeAttachment(
        array $attachment,
        ProjectCommunication $communication,
        ?Project $project
    ): ?ProjectDocument {
        $originalName = $attachment['Name'] ?? 'attachment';
        $content      = $attachment['Content'] ?? '';
        $contentType  = $attachment['ContentType'] ?? 'application/octet-stream';
        $contentLength = $attachment['ContentLength'] ?? null;

        if (empty($content)) {
            return null;
        }

        $decoded = base64_decode($content, strict: true);
        if ($decoded === false) {
            Log::error('InboundEmail: failed to base64-decode attachment', [
                'communication_id' => $communication->id,
                'name'             => $originalName,
            ]);
            return null;
        }

        // Build a collision-safe storage path
        $folder   = $project
            ? 'projects/' . $project->project_number
            : 'projects/unmatched';
        $folder  .= '/' . now()->format('Y-m');
        $slug     = Str::uuid() . '_' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME));
        $ext      = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = $ext ? "{$slug}.{$ext}" : $slug;
        $path     = "{$folder}/{$filename}";

        $disk = config('filesystems.disks.documents.driver', 'local');

        Storage::disk('documents')->put($path, $decoded);

        $document = ProjectDocument::create([
            'project_id'       => $project?->id,
            'communication_id' => $communication->id,
            'original_filename'=> $originalName,
            'storage_disk'     => $disk,
            'storage_path'     => $path,
            'mime_type'        => $contentType,
            'file_size_bytes'  => $contentLength ?? strlen($decoded),
            'document_type'    => 'email_attachment',
            'is_indexed'       => false,
        ]);

        Log::info('InboundEmail: stored attachment', [
            'document_id' => $document->id,
            'path'        => $path,
            'size'        => $document->file_size_bytes,
        ]);

        return $document;
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function findProject(string $projectNumber): ?Project
    {
        return Project::where('project_number', $projectNumber)->first();
    }

    private function parseDate(?string $date): ?\Carbon\Carbon
    {
        if (! $date) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }
}
