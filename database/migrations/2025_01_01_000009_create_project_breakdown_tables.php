<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Project breakdown: deliverables, milestones, tasks, task_assignments.
 * Also: schedule_of_fees (global billing rates), system_templates.
 *
 * Work Breakdown Structure (WBS):
 *   Project → Deliverables → Milestones → Tasks → Task Assignments
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('project_id');
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deliverable_id')->constrained('deliverables')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('deliverable_id');
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 20)->default('active');
            // Values: active | completed | overdue
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('milestone_id');
            $table->index('status');
        });

        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users');
            $table->string('role', 100)->nullable();
            $table->decimal('budget_hours', 8, 2)->default(0);
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('task_id');
            $table->index('user_id');
        });

        // ── Global schedule of fees ───────────────────────────────────────────
        // Admin-maintained billing rate table. Per-proposal rates in
        // proposal_rate_schedules override these.
        Schema::create('schedule_of_fees', function (Blueprint $table) {
            $table->id();
            $table->string('role_name', 100);
            $table->decimal('hourly_rate', 10, 2);
            $table->foreignId('project_type_id')->nullable()->constrained('project_types')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['role_name', 'effective_date']);
        });

        // ── System templates ──────────────────────────────────────────────────
        // Saved WBS templates that can be stamped onto new projects.
        Schema::create('system_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('work_type_id')->nullable()->constrained('work_types')->nullOnDelete();
            $table->jsonb('template_data')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_templates');
        Schema::dropIfExists('schedule_of_fees');
        Schema::dropIfExists('task_assignments');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('deliverables');
    }
};
