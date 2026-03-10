<?php

namespace App\Services;

use App\Models\DocumentChunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Semantic search over indexed project documents and email communications.
 *
 * Takes a natural-language query from a PM, embeds it via Ollama, then runs
 * a cosine similarity search against the document_chunks pgvector index.
 *
 * Usage:
 *   $results = app(ProjectContextSearchService::class)->search(
 *       query:     "what were the structural concerns raised by the engineer?",
 *       projectId: 42,
 *       limit:     5,
 *   );
 *
 *   // $results is a Collection of objects with:
 *   //   ->chunk_id
 *   //   ->chunk_text
 *   //   ->similarity          (0.0 – 1.0, higher is more similar)
 *   //   ->source_label        (filename or "Email: subject")
 *   //   ->project_document_id
 *   //   ->communication_id
 *
 * Phase 3 chat interface will use the top-K chunks as context passed to the
 * Ollama chat model alongside the PM's question.
 */
class ProjectContextSearchService
{
    /** Minimum cosine similarity to include in results (0.0 – 1.0). */
    private const MIN_SIMILARITY = 0.5;

    public function __construct(
        private readonly OllamaEmbeddingService $embedder
    ) {}

    /**
     * Semantic search for the most relevant document chunks for a query.
     *
     * @param  string    $query      Natural-language question or keyword string
     * @param  int|null  $projectId  Scope results to a specific project (recommended)
     * @param  int       $limit      Max results to return
     * @return Collection<object>
     */
    public function search(string $query, ?int $projectId = null, int $limit = 5): Collection
    {
        $vector  = $this->embedder->embed($query);
        $literal = OllamaEmbeddingService::toPgVectorLiteral($vector);

        $bindings = [$literal, $literal, self::MIN_SIMILARITY];

        $projectFilter = '';
        if ($projectId !== null) {
            $projectFilter = 'AND dc.project_id = ?';
            array_splice($bindings, 2, 0, [$projectId]);
        }

        // Bind limit last (PDO doesn't support binding in LIMIT clause directly)
        $limitPlaceholder = (int) $limit;

        $sql = "
            SELECT
                dc.id                     AS chunk_id,
                dc.chunk_text,
                dc.project_document_id,
                dc.communication_id,
                dc.project_id,
                dc.metadata,
                1 - (dc.embedding <=> ?::vector)  AS similarity,
                pd.original_filename,
                pc.subject                AS email_subject
            FROM document_chunks dc
            LEFT JOIN project_documents pd ON pd.id = dc.project_document_id
            LEFT JOIN project_communications pc ON pc.id = dc.communication_id
            WHERE dc.embedding IS NOT NULL
              {$projectFilter}
              AND 1 - (dc.embedding <=> ?::vector) >= ?
            ORDER BY dc.embedding <=> ?::vector
            LIMIT {$limitPlaceholder}
        ";

        // We pass the literal twice more: once for similarity calc, once for ORDER BY
        $bindings[] = $literal; // ORDER BY

        $rows = DB::select($sql, $bindings);

        return collect($rows)->map(function (object $row) {
            // Build a human-readable source label
            $row->source_label = $row->original_filename
                ?? ($row->email_subject ? "Email: {$row->email_subject}" : 'Unknown source');

            return $row;
        });
    }

    /**
     * Build a context string for injection into an Ollama prompt.
     *
     * Each chunk is labelled with its source so the LLM can cite it.
     * Suitable for: "Answer this question using ONLY the context below."
     */
    public function buildPromptContext(string $query, ?int $projectId = null, int $limit = 5): string
    {
        $results = $this->search($query, $projectId, $limit);

        if ($results->isEmpty()) {
            return "No relevant project documents found for: \"{$query}\"";
        }

        $parts = ["Relevant project context (use only this information to answer):\n"];

        foreach ($results as $i => $result) {
            $n       = $i + 1;
            $sim     = round($result->similarity * 100, 1);
            $parts[] = "[Source {$n}: {$result->source_label} — {$sim}% match]\n{$result->chunk_text}\n";
        }

        return implode("\n---\n", $parts);
    }

    /**
     * Search across all projects (admin/global context queries).
     */
    public function searchGlobal(string $query, int $limit = 10): Collection
    {
        return $this->search($query, projectId: null, limit: $limit);
    }
}
