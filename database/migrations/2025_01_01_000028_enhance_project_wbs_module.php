<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4: Enhanced Project WBS Module
 *
 * Changes:
 *  - Tasks can now belong directly to a deliverable (no milestone required)
 *  - Add start_date to deliverables and milestones (computed from tasks, also manually settable)
 *  - Task dependencies: finish-to-start relationships with optional lag
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Tasks can sit directly under a deliverable (milestone optional) ─
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('deliverable_id')
                ->nullable()
                ->after('milestone_id')
                ->constrained('deliverables')
                ->cascadeOnDelete();
        });

        // Make milestone_id nullable — a task belongs to EITHER a milestone or a deliverable
        DB::statement('ALTER TABLE tasks ALTER COLUMN milestone_id DROP NOT NULL');

        // ── 2. Add start_date to deliverables ─────────────────────────────────
        Schema::table('deliverables', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('description');
        });

        // ── 3. Add start_date to milestones ───────────────────────────────────
        Schema::table('milestones', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('description');
        });

        // ── 4. Task dependencies ──────────────────────────────────────────────
        // Finish-to-start by default. lag_days > 0 = gap, < 0 = overlap.
        Schema::create('task_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('depends_on_id')->constrained('tasks')->cascadeOnDelete();
            $table->smallInteger('lag_days')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['task_id', 'depends_on_id']);
            $table->index('task_id');
            $table->index('depends_on_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_dependencies');

        Schema::table('milestones', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });

        Schema::table('deliverables', function (Blueprint $table) {
            $table->dropColumn('start_date');
        });

        // Restore NOT NULL on milestone_id (only safe if all tasks have a milestone)
        DB::statement('UPDATE tasks SET milestone_id = (SELECT id FROM milestones LIMIT 1) WHERE milestone_id IS NULL');
        DB::statement('ALTER TABLE tasks ALTER COLUMN milestone_id SET NOT NULL');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['deliverable_id']);
            $table->dropColumn('deliverable_id');
        });
    }
};
