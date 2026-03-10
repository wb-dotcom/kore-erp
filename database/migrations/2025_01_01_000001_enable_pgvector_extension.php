<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Enable the pgvector extension.
 *
 * pgvector provides the `vector` column type used in Phase 3 for
 * storing document/email embedding vectors and running ANN similarity search.
 *
 * Requires: pgvector/pgvector:pg16 Docker image (already in docker-compose.yml).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
    }

    public function down(): void
    {
        DB::statement('DROP EXTENSION IF EXISTS vector');
    }
};
