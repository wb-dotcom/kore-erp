<?php

namespace App\Jobs;

use App\Models\DocumentChunk;
use App\Models\ProjectDocument;
use App\Services\DocumentTextExtractor;
use App\Services\OllamaEmbeddingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Extracts text from a ProjectDocument, chunks it, embeds each chunk via Ollama,
 * and stores the vectors in document_chunks for similarity search (Phase 3 RAG).
 *
 * Triggered automatically when:
 *   - InboundEmailService stores an email attachment (Phase 2 → Phase 3 bridge)
 *   - A PM manually uploads a document (future: via document upload controller)
 *
 * The job is idempotent: if the document is already indexed, it clears and re-indexes
 * (useful when Ollama model changes or a document is replaced).
 *
 * Timeout is set to 5 minutes to accommodate large PDFs on slower hardware.
 * The Mac M4 host runs Ollama — embedding 50 chunks typically takes < 30s.
 */
class IndexProjectDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public array $backoff = [60, 180, 300];

    public function __construct(
        private readonly int $documentId
    ) {}

    public function handle(
        DocumentTextExtractor $extractor,
        OllamaEmbeddingService $embedder
    ): void {
        $document = ProjectDocument::find($this->documentId);

        if (! $document) {
            Log::warning("IndexProjectDocument: document {$this->documentId} not found, skipping.");
            return;
        }

        if (! $document->isTextExtractable()) {
            Log::info("IndexProjectDocument: skipping non-text document", [
                'document_id' => $document->id,
                'mime_type'   => $document->mime_type,
            ]);
            return;
        }

        Log::info("IndexProjectDocument: starting indexing", [
            'document_id' => $document->id,
            'filename'    => $document->original_filename,
        ]);

        // Extract and chunk text
        $chunks = $extractor->extractChunks($document);

        if (empty($chunks)) {
            Log::warning("IndexProjectDocument: no text extracted", ['document_id' => $document->id]);
            $this->markIndexed($document, 0);
            return;
        }

        // Clear existing chunks for this document (idempotent re-index)
        DocumentChunk::where('project_document_id', $document->id)->delete();

        // Embed all chunks via Ollama
        $vectors = $embedder->embedBatch($chunks);

        // Persist chunks with their embedding vectors
        DB::transaction(function () use ($document, $chunks, $vectors) {
            foreach ($chunks as $i => $chunkText) {
                $vector  = $vectors[$i];
                $literal = OllamaEmbeddingService::toPgVectorLiteral($vector);

                // Insert via raw SQL to set the vector(768) column
                // Eloquent does not know the vector type; we bind all other fields normally.
                DB::statement(
                    'INSERT INTO document_chunks
                        (project_document_id, communication_id, project_id, chunk_index, chunk_text, token_count, metadata, embedding, created_at)
                     VALUES
                        (?, ?, ?, ?, ?, ?, ?::jsonb, ?::vector, NOW())',
                    [
                        $document->id,
                        $document->communication_id,
                        $document->project_id,
                        $i,
                        $chunkText,
                        $this->estimateTokens($chunkText),
                        json_encode(['chunk_count' => count($chunks)]),
                        $literal,
                    ]
                );
            }
        });

        $this->markIndexed($document, count($chunks));

        Log::info("IndexProjectDocument: indexed {$document->id}", [
            'filename'    => $document->original_filename,
            'chunk_count' => count($chunks),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error("IndexProjectDocument: permanently failed for document {$this->documentId}", [
            'error' => $e->getMessage(),
        ]);

        // Mark as not indexed so the UI can show a warning
        ProjectDocument::where('id', $this->documentId)->update([
            'is_indexed' => false,
            'indexed_at' => null,
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function markIndexed(ProjectDocument $document, int $chunkCount): void
    {
        $document->update([
            'is_indexed' => true,
            'indexed_at' => now(),
        ]);
    }

    /** Rough token estimate: 1 token ≈ 4 chars for English text. */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
