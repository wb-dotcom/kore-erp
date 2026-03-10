<?php

namespace App\Http\Controllers;

use App\Models\ProjectDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves project documents stored on the configured disk.
 *
 * Works with both local (storage/app/documents) and S3 disks.
 * The disk name is stored per-record in storage_disk, so this
 * remains correct if the default disk changes between environments.
 *
 * Route: GET /documents/{document}/download  → documents.download
 */
class DocumentController extends Controller
{
    /**
     * Stream the document file to the browser as a download.
     *
     * For S3-stored files: redirects to a 60-minute pre-signed URL.
     * For local files: streams directly through the app (avoids exposing storage path).
     */
    public function download(ProjectDocument $document): Response|StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        // Verify the authenticated user can access this document.
        // Documents are accessible to any logged-in user (project-level auth is at the project layer).
        if (! auth()->check()) {
            abort(403);
        }

        $disk = Storage::disk($document->storage_disk);

        if (! $disk->exists($document->storage_path)) {
            abort(404, 'Document file not found on storage disk.');
        }

        // For S3: use a temporary pre-signed URL (faster, offloads bandwidth)
        if ($document->storage_disk === 's3') {
            $url = $disk->temporaryUrl($document->storage_path, now()->addMinutes(60));
            return redirect()->away($url);
        }

        // For local disk: stream through the application
        $mime     = $document->mime_type ?? 'application/octet-stream';
        $filename = $document->original_filename;

        return response()->streamDownload(function () use ($disk, $document) {
            $stream = $disk->readStream($document->storage_path);
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, $filename, [
            'Content-Type'        => $mime,
            'Content-Disposition' => 'attachment; filename="' . addslashes($filename) . '"',
        ]);
    }
}
