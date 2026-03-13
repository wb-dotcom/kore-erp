<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Enhance project templates to support:
 *  - Billing type + billing cycle tagging on templates
 *  - Max hours cap on deliverables
 *  - Dependency chains (depends_on) at deliverable, activity, and task levels
 *  - Direct tasks under deliverables (optional milestone grouping)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. activity_templates ──────────────────────────────────────────────
        Schema::table('activity_templates', function (Blueprint $table) {
            // fixed | time_and_material | hybrid | retainer | per_deliverable | null = any
            $table->string('billing_type', 30)->nullable()->after('work_type_id');
            // biweekly | monthly | quarterly | on_completion | custom | null = any
            $table->string('billing_cycle', 30)->nullable()->after('billing_type');
        });

        // ── 2. activity_template_deliverables ──────────────────────────────────
        Schema::table('activity_template_deliverables', function (Blueprint $table) {
            // Total hours budget for this deliverable (milestones + tasks inside must not exceed)
            $table->decimal('max_hours', 8, 2)->nullable()->after('sort_order');
            // Optional dependency: this deliverable starts after another completes
            $table->unsignedBigInteger('depends_on_deliverable_id')->nullable()->after('max_hours');
            $table->foreign('depends_on_deliverable_id')
                ->references('id')->on('activity_template_deliverables')
                ->nullOnDelete();
        });

        // ── 3. activity_template_activities ───────────────────────────────────
        Schema::table('activity_template_activities', function (Blueprint $table) {
            // Optional dependency: this milestone starts after another milestone completes
            $table->unsignedBigInteger('depends_on_activity_id')->nullable()->after('sort_order');
            $table->foreign('depends_on_activity_id')
                ->references('id')->on('activity_template_activities')
                ->nullOnDelete();
        });

        // ── 4. activity_template_tasks ────────────────────────────────────────
        Schema::table('activity_template_tasks', function (Blueprint $table) {
            // Allow tasks to live directly under a deliverable (no milestone required)
            $table->unsignedBigInteger('activity_template_deliverable_id')
                ->nullable()->after('activity_template_activity_id');
            $table->foreign('activity_template_deliverable_id')
                ->references('id')->on('activity_template_deliverables')
                ->cascadeOnDelete();

            // Make the milestone FK nullable (task can now be direct-under-deliverable)
            $table->unsignedBigInteger('activity_template_activity_id')
                ->nullable()->change();

            // Optional task dependency: this task starts after another task completes
            $table->unsignedBigInteger('depends_on_task_id')->nullable()->after('sort_order');
            $table->foreign('depends_on_task_id')
                ->references('id')->on('activity_template_tasks')
                ->nullOnDelete();
        });

        // ── 5. proposal_deliverables ───────────────────────────────────────────
        Schema::table('proposal_deliverables', function (Blueprint $table) {
            $table->decimal('max_hours', 8, 2)->nullable()->after('sort_order');
            $table->unsignedBigInteger('depends_on_deliverable_id')->nullable()->after('max_hours');
            $table->foreign('depends_on_deliverable_id')
                ->references('id')->on('proposal_deliverables')
                ->nullOnDelete();
        });

        // ── 6. proposal_activities ─────────────────────────────────────────────
        Schema::table('proposal_activities', function (Blueprint $table) {
            $table->unsignedBigInteger('depends_on_activity_id')->nullable()->after('sort_order');
            $table->foreign('depends_on_activity_id')
                ->references('id')->on('proposal_activities')
                ->nullOnDelete();
        });

        // ── 7. proposal_tasks ──────────────────────────────────────────────────
        Schema::table('proposal_tasks', function (Blueprint $table) {
            // Allow direct tasks under a proposal deliverable (no activity/milestone required)
            $table->unsignedBigInteger('proposal_deliverable_id')
                ->nullable()->after('proposal_activity_id');
            $table->foreign('proposal_deliverable_id')
                ->references('id')->on('proposal_deliverables')
                ->cascadeOnDelete();

            // Make activity FK nullable
            $table->unsignedBigInteger('proposal_activity_id')
                ->nullable()->change();

            $table->unsignedBigInteger('depends_on_task_id')->nullable()->after('sort_order');
            $table->foreign('depends_on_task_id')
                ->references('id')->on('proposal_tasks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proposal_tasks', function (Blueprint $table) {
            $table->dropForeign(['depends_on_task_id']);
            $table->dropForeign(['proposal_deliverable_id']);
            $table->dropColumn(['depends_on_task_id', 'proposal_deliverable_id']);
        });

        Schema::table('proposal_activities', function (Blueprint $table) {
            $table->dropForeign(['depends_on_activity_id']);
            $table->dropColumn('depends_on_activity_id');
        });

        Schema::table('proposal_deliverables', function (Blueprint $table) {
            $table->dropForeign(['depends_on_deliverable_id']);
            $table->dropColumn(['max_hours', 'depends_on_deliverable_id']);
        });

        Schema::table('activity_template_tasks', function (Blueprint $table) {
            $table->dropForeign(['depends_on_task_id']);
            $table->dropForeign(['activity_template_deliverable_id']);
            $table->dropColumn(['depends_on_task_id', 'activity_template_deliverable_id']);
        });

        Schema::table('activity_template_activities', function (Blueprint $table) {
            $table->dropForeign(['depends_on_activity_id']);
            $table->dropColumn('depends_on_activity_id');
        });

        Schema::table('activity_template_deliverables', function (Blueprint $table) {
            $table->dropForeign(['depends_on_deliverable_id']);
            $table->dropColumn(['max_hours', 'depends_on_deliverable_id']);
        });

        Schema::table('activity_templates', function (Blueprint $table) {
            $table->dropColumn(['billing_type', 'billing_cycle']);
        });
    }
};
