<?php

namespace App\Jobs;

use App\Models\DocumentChunk;
use App\Models\ProjectCommunication;
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
 * Indexes the body text of an inbound email into the pgvector store.
 *
 * Chunks the combined subject + body_text of a ProjectCommunication and stores
 * embeddings in document_chunks so emails are searchable via RAG alongside files.
 *
 * Chunking strategy:
 *   - Paragraphs split on double-newline
 *   - Target chunk size: 1500 chars with 150-char overlap
 *   - Subject is prepended to the first chunk for context
 *
 * Only dispatched when the communication has body_text content.
 * Idempotent: clears existing communication chunks before re-indexing.
 */
class IndexProjectCommunication implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 120;

    public array $backoff = [30, 90, 180];

    public function __construct(
        private readonly int $communicationId
    ) {}

    public function handle(OllamaEmbeddingService $embedder): void
    {
        $communication = ProjectCommunication::find($this->communicationId);

        if (! $communication) {
            Log::warning("IndexProjectCommunication: communication {$this->communicationId} not found, skipping.");
            return;
        }

        $bodyText = trim($communication->body_text ?? '');

        if (empty($bodyText)) {
            Log::info("IndexProjectCommunication: no body text, skipping.", [
                'communication_id' => $communication->id,
            ]);
            return;
        }

        Log::info("IndexProjectCommunication: starting indexing", [
            'communication_id' => $communication->id,
            'subject'          => $communication->subject,
        ]);

        $chunks = $this->chunkText($communication->subject, $bodyText);

        if (empty($chunks)) {
            return;
        }

        // Idempotent: remove existing chunks for this communication (no document)
        DocumentChunk::where('communication_id', $communication->id)
            ->whereNull('project_document_id')
            ->delete();

        $vectors = $embedder->embedBatch($chunks);

        DB::transaction(function () use ($communication, $chunks, $vectors) {
            foreach ($chunks as $i => $chunkText) {
                $vector  = $vectors[$i];
                $literal = OllamaEmbeddingService::toPgVectorLiteral($vector);

                DB::statement(
                    'INSERT INTO document_chunks
                        (project_document_id, communication_id, project_id, chunk_index, chunk_text, token_count, metadata, embedding, created_at)
                     VALUES
                        (NULL, ?, ?, ?, ?, ?, ?::jsonb, ?::vector, NOW())',
                    [
                        $communication->id,
                        $communication->project_id,
                        $i,
                        $chunkText,
                        $this->estimateTokens($chunkText),
                        json_encode([
                            'source'  => 'email',
                            'subject' => $communication->subject,
                            'from'    => $communication->from_email,
                        ]),
                        $literal,
                    ]
                );
            }
        });

        Log::info("IndexProjectCommunication: indexed communication {$communication->id}", [
            'chunk_count' => count($chunks),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error("IndexProjectCommunication: permanently failed for communication {$this->communicationId}", [
            'error' => $e->getMessage(),
        ]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * Split email body into overlapping chunks, prepending subject to the first.
     *
     * @return string[]
     */
    private function chunkText(string $subject, string $bodyText): array
    {
        $targetSize = 1500;
        $overlap    = 150;

        // Normalise line endings and split into paragraphs
        $bodyText   = str_replace(["\r\n", "\r"], "\n", $bodyText);
        $paragraphs = preg_split('/\n{2,}/', $bodyText);
        $paragraphs = array_values(array_filter(array_map('trim', $paragraphs)));

        if (empty($paragraphs)) {
            return [];
        }

        $chunks = [];
        $buffer = '';

        foreach ($paragraphs as $para) {
            if (strlen($buffer) + strlen($para) + 2 > $targetSize && $buffer !== '') {
                $chunks[] = $buffer;

                // Overlap: carry the tail of the previous chunk
                $tail   = substr($buffer, -$overlap);
                $buffer = $tail . "\n\n" . $para;
            } else {
                $buffer = $buffer === '' ? $para : $buffer . "\n\n" . $para;
            }
        }

        if (trim($buffer) !== '') {
            $chunks[] = trim($buffer);
        }

        // Prepend subject to first chunk for better retrieval relevance
        if (! empty($chunks) && $subject) {
            $chunks[0] = "Subject: {$subject}\n\n" . $chunks[0];
        }

        return $chunks;
    }

    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }
}
