<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2: Inbound email capture tables.
 *
 * project_communications — every inbound email captured by the webhook.
 *   Matched to a project via project_number extracted from the subject line.
 *   Unmatched emails are kept (processing_status = 'unmatched') for manual triage.
 *
 * project_documents — every file stored to disk (email attachments + future manual uploads).
 *   Decoupled from communications so a document can exist without an email parent
 *   (e.g., a PM manually uploads a CAD file to a project).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_communications', function (Blueprint $table) {
            $table->id();

            // Nullable: set when we successfully match a project_number from subject.
            $table->foreignId('project_id')
                  ->nullable()
                  ->constrained('projects')
                  ->nullOnDelete();

            // Postmark envelope fields
            $table->string('message_id', 255)->unique()->nullable();
            $table->string('from_email', 150);
            $table->string('from_name', 150)->nullable();
            $table->text('to_email');
            $table->string('subject', 998)->nullable();
            $table->timestamp('sent_at')->nullable();

            // Parsed body — we keep both; Phase 3 RAG pipeline reads text_body.
            $table->text('body_text')->nullable();
            $table->text('body_html')->nullable();

            // The project_number string extracted from the subject line.
            // Stored so we can re-run matching logic without re-parsing raw_payload.
            $table->string('extracted_project_number', 20)->nullable();

            // Processing lifecycle
            $table->string('processing_status', 20)->default('pending');
            // Values: pending | matched | unmatched | error

            $table->text('processing_error')->nullable();

            // Full Postmark JSON payload for debugging and reprocessing.
            $table->jsonb('raw_payload');

            $table->timestamps();

            $table->index('project_id');
            $table->index('processing_status');
            $table->index('from_email');
            $table->index('sent_at');
        });

        Schema::create('project_documents', function (Blueprint $table) {
            $table->id();

            // Project association — nullable so a document can be staged before matching.
            $table->foreignId('project_id')
                  ->nullable()
                  ->constrained('projects')
                  ->nullOnDelete();

            // Phase association — set when document is linked to a specific AIA stage.
            $table->foreignId('project_phase_id')
                  ->nullable()
                  ->constrained('project_phases')
                  ->nullOnDelete();

            // Source communication (if this doc came from an email attachment).
            $table->foreignId('communication_id')
                  ->nullable()
                  ->constrained('project_communications')
                  ->nullOnDelete();

            // File metadata
            $table->string('original_filename', 500);
            $table->string('storage_disk', 20)->default('local');
            // Values: local | s3 — records which disk was used at upload time.

            $table->string('storage_path', 1000);
            // Relative path on the disk, e.g. projects/2025-001/comms/2025-01-15/report.pdf

            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('file_size_bytes')->nullable();

            // Category for search/filter
            $table->string('document_type', 30)->default('email_attachment');
            // Values: email_attachment | manual_upload | generated | drawing | permit | contract

            // Phase 3: set when the RAG pipeline has processed this document.
            $table->boolean('is_indexed')->default(false);
            $table->timestamp('indexed_at')->nullable();

            $table->foreignId('created_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();

            $table->index('project_id');
            $table->index('project_phase_id');
            $table->index('communication_id');
            $table->index('document_type');
            $table->index('is_indexed');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
        Schema::dropIfExists('project_communications');
    }
};
