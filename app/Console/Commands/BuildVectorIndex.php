<?php

namespace App\Console\Commands;

use App\Jobs\IndexProjectDocument;
use App\Models\DocumentChunk;
use App\Models\ProjectDocument;
use Illuminate\Console\Command;

/**
 * Batch re-index all project documents into the pgvector store.
 *
 * Run this after:
 *   - Installing/changing the Ollama embedding model
 *   - A bulk document import
 *   - Initial deployment to index existing files
 *
 * Usage:
 *   php artisan kore:build-vector-index                 # queue all unindexed
 *   php artisan kore:build-vector-index --all           # re-index everything (clears existing)
 *   php artisan kore:build-vector-index --project=42    # only one project
 *   php artisan kore:build-vector-index --sync          # run synchronously (no queue)
 */
class BuildVectorIndex extends Command
{
    protected $signature = 'kore:build-vector-index
                            {--all : Re-index all documents, even already-indexed ones}
                            {--project= : Only index documents for a specific project ID}
                            {--sync : Run indexing synchronously instead of queuing}';

    protected $description = 'Index project documents into the pgvector RAG store';

    public function handle(): int
    {
        $all       = $this->option('all');
        $projectId = $this->option('project');
        $sync      = $this->option('sync');

        $query = ProjectDocument::query()->whereNotNull('mime_type');

        if ($projectId) {
            $query->where('project_id', $projectId);
            $this->info("Indexing documents for project ID: {$projectId}");
        }

        if ($all) {
            $this->warn('--all flag: clearing existing chunks and re-indexing everything.');
        } else {
            $query->where('is_indexed', false);
        }

        // Filter to text-extractable MIME types only
        $query->whereIn('mime_type', [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'text/html',
        ]);

        $documents = $query->get();

        if ($documents->isEmpty()) {
            $this->info('No documents to index.');
            return 0;
        }

        $this->info("Found {$documents->count()} document(s) to index.");

        if ($all) {
            // Clear existing chunks for selected documents
            $ids = $documents->pluck('id');
            $deleted = DocumentChunk::whereIn('project_document_id', $ids)->delete();
            $this->line("Cleared {$deleted} existing chunk(s).");

            // Mark all as not indexed so jobs know to re-index
            ProjectDocument::whereIn('id', $ids)->update(['is_indexed' => false, 'indexed_at' => null]);
        }

        $bar = $this->output->createProgressBar($documents->count());
        $bar->start();

        $queued = 0;
        foreach ($documents as $document) {
            if ($sync) {
                dispatch_sync(new IndexProjectDocument($document->id));
            } else {
                IndexProjectDocument::dispatch($document->id)->onQueue('indexing');
            }
            $queued++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($sync) {
            $this->info("✓ Indexed {$queued} document(s) synchronously.");
        } else {
            $this->info("✓ Queued {$queued} document(s) for indexing.");
            $this->line('Run queue worker:  php artisan queue:work --queue=indexing');
        }

        return 0;
    }
}
