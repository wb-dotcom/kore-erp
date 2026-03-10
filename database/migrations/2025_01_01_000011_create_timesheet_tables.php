<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timesheet tables: periods, timesheets, entries, PTO policies, time-off requests, holidays.
 *
 * Key Phase 1 addition: timesheet_entries now has project_phase_id.
 * This is the financial join point:
 *   entry.hours × effective_rate = actual_labor_cost_for_phase
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('timesheet_periods', function (Blueprint $table) {
            $table->id();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('due_date')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['start_date', 'end_date']);
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('timesheets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('period_id')->constrained('timesheet_periods');
            $table->string('status', 20)->default('draft');
            // Values: draft | submitted | approved | rejected
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'period_id']);
            $table->index('status');
            $table->index('user_id');
        });

        Schema::create('timesheet_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timesheet_id')->constrained('timesheets')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects');

            // ── Phase 1: Phase-level tracking ─────────────────────────────────
            // Nullable: non-billable entries (admin, PTO) may not have a phase.
            $table->foreignId('project_phase_id')
                  ->nullable()
                  ->constrained('project_phases')
                  ->nullOnDelete();

            $table->foreignId('deliverable_id')->nullable()->constrained('deliverables')->nullOnDelete();
            $table->foreignId('milestone_id')->nullable()->constrained('milestones')->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained('tasks')->nullOnDelete();

            $table->date('entry_date');
            $table->decimal('hours', 5, 2);
            $table->string('entry_type', 20)->default('billable');
            // Values: billable | non_billable | pto | unpaid | remote_work

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('timesheet_id');
            $table->index('project_id');
            $table->index('project_phase_id');
            $table->index('entry_date');
        });

        Schema::create('pto_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->decimal('annual_pto_hours', 8, 2)->default(0);
            $table->decimal('carry_over_hours', 8, 2)->default(0);
            $table->date('effective_date')->nullable();
            $table->timestamps();
        });

        Schema::create('time_off_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('request_type', 20);
            // Values: pto | unpaid | remote_work
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('hours', 8, 2)->nullable();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending');
            // Values: pending | approved | rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->date('holiday_date')->unique();
            $table->unsignedSmallInteger('year');
            $table->timestamp('created_at')->useCurrent();

            $table->index('year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('time_off_requests');
        Schema::dropIfExists('pto_policies');
        Schema::dropIfExists('timesheet_entries');
        Schema::dropIfExists('timesheets');
        Schema::dropIfExists('timesheet_periods');
    }
};
