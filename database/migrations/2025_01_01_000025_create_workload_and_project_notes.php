<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Workload pipeline + PM notes.
 *
 * work_schedule_items: Allows users to schedule task assignments into specific
 *   weeks with planned hours and priority ordering. Feeds the timesheet as
 *   quick-fill options for the scheduled week.
 *
 * project_notes: PM-level notes and todos attached to a project.
 *
 * project_documents: Extended with optional WBS foreign keys so files can be
 *   attached at the deliverable / milestone / task level.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Work Schedule Items ────────────────────────────────────────────────
        Schema::create('work_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_assignment_id')
                  ->constrained('task_assignments')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->date('week_start');          // Monday of the scheduled week
            $table->decimal('scheduled_hours', 5, 2)->default(0);
            $table->unsignedSmallInteger('priority_order')->default(100);
            $table->string('status', 20)->default('planned');
            // Values: planned | done | carried_over
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'week_start']);
            $table->index('task_assignment_id');
            $table->unique(['task_assignment_id', 'week_start']);
        });

        // ── Project Notes / PM Todos ───────────────────────────────────────────
        Schema::create('project_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->text('content');
            $table->boolean('is_todo')->default(false);
            $table->boolean('is_done')->default(false);
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('priority_order')->default(100);
            $table->timestamps();

            $table->index(['project_id', 'is_done']);
            $table->index('user_id');
        });

        // ── Extend project_documents with WBS scope ────────────────────────────
        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('project_documents', 'deliverable_id')) {
                    $table->foreignId('deliverable_id')
                          ->nullable()
                          ->constrained('deliverables')
                          ->nullOnDelete()
                          ->after('project_id');
                }
                if (! Schema::hasColumn('project_documents', 'milestone_id')) {
                    $table->foreignId('milestone_id')
                          ->nullable()
                          ->constrained('milestones')
                          ->nullOnDelete()
                          ->after('deliverable_id');
                }
                if (! Schema::hasColumn('project_documents', 'task_id')) {
                    $table->foreignId('task_id')
                          ->nullable()
                          ->constrained('tasks')
                          ->nullOnDelete()
                          ->after('milestone_id');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedule_items');
        Schema::dropIfExists('project_notes');

        if (Schema::hasTable('project_documents')) {
            Schema::table('project_documents', function (Blueprint $table) {
                $table->dropForeignIdFor(\App\Models\Task::class);
                $table->dropForeignIdFor(\App\Models\Milestone::class);
                $table->dropForeignIdFor(\App\Models\Deliverable::class);
            });
        }
    }
};
