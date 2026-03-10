<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kore AI — conversation persistence and message history.
 *
 * ai_conversations
 *   One row per chat session. Scoped to a user; optionally scoped to a project
 *   (so the AI automatically focuses on that project's context).
 *
 * ai_messages
 *   One row per message turn (user or assistant). The full conversation history
 *   is replayed to Ollama on each message so the model has memory.
 *   Assistant messages include the RAG sources that were injected as context.
 *
 * Design notes:
 *   - model is stored per-conversation so switching models mid-session is safe
 *   - sources (jsonb) on assistant messages records which documents/emails
 *     were used as RAG context — enables the "Sources" panel in the UI
 *   - processing_time_ms enables latency monitoring across models
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Short title (auto-set from first user message or user-renamed)
            $table->string('title', 200)->nullable();

            // Which Ollama model powers this conversation (e.g. llama3:8b, mistral:7b)
            $table->string('model', 100)->default('llama3');

            // Optional: scope this conversation to a single project.
            // When set, the AI always prefetches that project's full context.
            $table->foreignId('context_project_id')
                  ->nullable()
                  ->constrained('projects')
                  ->nullOnDelete();

            // Cached message count for sidebar display (updated after each turn)
            $table->unsignedSmallInteger('message_count')->default(0);

            $table->timestamps();

            $table->index('user_id');
            $table->index('context_project_id');
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')
                  ->constrained('ai_conversations')
                  ->cascadeOnDelete();

            // user | assistant | system
            $table->string('role', 20);

            // Full message text (markdown for assistant, plain for user)
            $table->text('content');

            // Which model produced this response (null for user messages)
            $table->string('model', 100)->nullable();

            // RAG and structured data sources injected as context for this response
            // [{"type": "project", "label": "2025-001"}, {"type": "document", "label": "plans.pdf"}]
            $table->jsonb('sources')->nullable();

            // Round-trip latency from request to last chunk received
            $table->unsignedInteger('processing_time_ms')->nullable();

            // Approximate token count (Ollama reports eval_count on done message)
            $table->unsignedInteger('token_count')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('conversation_id');
        });

        // Seed default AI settings
        DB::table('system_settings')->insertOrIgnore([
            ['setting_key' => 'ai_enabled',       'setting_value' => '1'],
            ['setting_key' => 'ai_default_model',  'setting_value' => 'llama3'],
            ['setting_key' => 'ai_context_window', 'setting_value' => '4096'],
            ['setting_key' => 'ai_temperature',    'setting_value' => '0.7'],
            // Comma-separated list of model names the admin has enabled for users
            ['setting_key' => 'ai_enabled_models', 'setting_value' => 'llama3,mistral,phi3:mini'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
