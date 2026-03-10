<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3: RAG Pipeline — document_chunks.
 *
 * Each row is one text chunk extracted from a ProjectDocument or ProjectCommunication.
 * The embedding column holds a 768-dimensional vector produced by Ollama nomic-embed-text.
 *
 * pgvector was enabled in migration 000001. The vector() column type is not
 * supported by Laravel's Schema builder, so it's added via raw SQL after table creation.
 *
 * Index Strategy:
 *   IVFFlat index for approximate nearest-neighbour (ANN) search.
 *   lists = sqrt(row_count) is a common rule of thumb; start with 100.
 *   The index is created AFTER initial data load (via `php artisan kore:build-vector-index`).
 *   For now, exact L2/cosine scan is used (fine up to ~100k chunks).
 *
 *   To build the HNSW index (better recall, slower build):
 *     CREATE INDEX ON document_chunks USING hnsw (embedding vector_cosine_ops);
 *
 * Cosine similarity query:
 *   SELECT *, 1 - (embedding <=> '[...]'::vector) as similarity
 *   FROM document_chunks
 *   WHERE project_id = ?
 *   ORDER BY embedding <=> '[...]'::vector
 *   LIMIT 5;
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_chunks', function (Blueprint $table) {
            $table->id();

            // Source document
            $table->foreignId('project_document_id')
                  ->nullable()
                  ->constrained('project_documents')
                  ->nullOnDelete();

            // Source communication (for email body chunks)
            $table->foreignId('communication_id')
                  ->nullable()
                  ->constrained('project_communications')
                  ->nullOnDelete();

            // Denormalised for fast WHERE filtering during similarity search
            $table->foreignId('project_id')
                  ->nullable()
                  ->constrained('projects')
                  ->nullOnDelete();

            // Sequential chunk number within the source document (0-based)
            $table->unsignedSmallInteger('chunk_index')->default(0);

            // The raw text of this chunk (~500 tokens / ~2000 chars)
            $table->text('chunk_text');

            // Approximate token count (used for context window management in Phase 3 chat)
            $table->unsignedSmallInteger('token_count')->nullable();

            // Additional metadata: source page number, section heading, email subject, etc.
            $table->jsonb('metadata')->nullable();

            // Indexed timestamp
            $table->timestamp('created_at')->useCurrent();

            $table->index('project_document_id');
            $table->index('communication_id');
            $table->index('project_id');
        });

        // Add the vector(768) column — nomic-embed-text produces 768-dimensional vectors.
        // This cannot be done via the Schema builder; pgvector requires raw DDL.
        DB::statement('ALTER TABLE document_chunks ADD COLUMN embedding vector(768)');

        // IVFFlat index for cosine ANN search.
        // Skip if table is empty (index build requires at least 1 row in some pgvector versions).
        // Run `php artisan kore:build-vector-index` after initial bulk import.
        //
        // DB::statement('CREATE INDEX ON document_chunks USING ivfflat (embedding vector_cosine_ops) WITH (lists = 100)');
    }

    public function down(): void
    {
        Schema::dropIfExists('document_chunks');
    }
};
