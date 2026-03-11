<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2: Enhanced Proposal Module
 *
 * New capabilities:
 *  - Proposals: contact, vendor code, project type, contract value, expenses reserve,
 *               expiry date, program link, google doc URL
 *  - Billing Schedules: auto-generated invoice periods per proposal
 *  - Proposal Deliverables / Activities / Tasks: 3-level work breakdown template
 *  - Activity Templates: reusable system-wide templates (admin-managed)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Enhance proposals table ─────────────────────────────────────────
        Schema::table('proposals', function (Blueprint $table) {
            // Client contact person
            $table->foreignId('contact_id')->nullable()->after('company_id')
                ->constrained('contacts')->nullOnDelete();

            // Agency's vendor code with the client (e.g. "BPHGA" for Ford)
            $table->string('vendor_code', 50)->nullable()->after('po_number');

            // Project type (Billable, Internal, Non-Billable)
            $table->foreignId('project_type_id')->nullable()->after('work_type_id')
                ->constrained('project_types')->nullOnDelete();

            // Contract value (what client agreed to pay — may differ from fee worksheet total)
            $table->decimal('contract_value', 14, 2)->nullable()->after('total_fee');

            // Separate budget for reimbursable expenses (not included in professional fees)
            $table->decimal('expenses_reserve', 14, 2)->default(0)->after('contract_value');

            // Expiry date — when the proposal lapses if not approved
            $table->date('expiry_date')->nullable()->after('approved_date');

            // Link to a rollout program (umbrella grouping)
            $table->foreignId('program_id')->nullable()->after('created_by')
                ->constrained('programs')->nullOnDelete();

            // Google Doc URL for proposal content editing
            $table->string('google_doc_url', 500)->nullable()->after('program_id');
            $table->timestamp('google_doc_synced_at')->nullable()->after('google_doc_url');
        });

        // ── 2. Billing schedules ───────────────────────────────────────────────
        Schema::create('billing_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();

            // fixed | time_and_material | per_deliverable | retainer
            $table->string('billing_type', 30)->default('fixed');

            // biweekly | monthly | quarterly | custom
            $table->string('billing_cycle', 20)->default('monthly');

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            // Whether expense amounts are included in each billing period
            $table->boolean('include_expenses')->default(false);

            // Net payment terms (copied from proposal, can be overridden)
            $table->unsignedSmallInteger('payment_terms_days')->default(30);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('proposal_id');
        });

        // ── 3. Billing schedule periods (one row per invoice period) ──────────
        Schema::create('billing_schedule_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('billing_schedule_id')->constrained('billing_schedules')->cascadeOnDelete();

            $table->date('period_start');
            $table->date('period_end');

            // Professional fees amount for this period
            $table->decimal('fees_amount', 14, 2)->default(0);

            // Expenses amount for this period
            $table->decimal('expenses_amount', 14, 2)->default(0);

            // Total = fees + expenses (computed, stored for fast reads)
            $table->decimal('total_amount', 14, 2)->default(0);

            // draft | approved | invoiced | paid | overdue
            $table->string('status', 20)->default('draft');

            // Link to actual invoice once generated
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('invoice_number', 50)->nullable();

            // Lock period once invoiced — prevents accidental changes
            $table->boolean('is_locked')->default(false);

            $table->text('notes')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('billing_schedule_id');
            $table->index('status');
        });

        // ── 4. Proposal deliverables (top-level grouping) ─────────────────────
        Schema::create('proposal_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('proposal_id');
        });

        // ── 5. Proposal activities (replaces milestones — under a deliverable) ─
        Schema::create('proposal_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_deliverable_id')
                ->constrained('proposal_deliverables')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            // Relative scheduling (days from project start) — converted to real dates when project is created
            $table->unsignedSmallInteger('relative_start_day')->nullable();
            $table->unsignedSmallInteger('relative_end_day')->nullable();

            // Role assigned (generic role name at proposal stage, maps to user at project stage)
            $table->string('assigned_role', 100)->nullable();

            // Budget hours for this activity
            $table->decimal('budgeted_hours', 8, 2)->default(0);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('proposal_deliverable_id');
        });

        // ── 6. Proposal tasks (under an activity) ─────────────────────────────
        Schema::create('proposal_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_activity_id')
                ->constrained('proposal_activities')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();

            // Relative due day (from project start)
            $table->unsignedSmallInteger('relative_due_day')->nullable();

            // Role assigned at proposal stage
            $table->string('assigned_role', 100)->nullable();

            // Estimated hours for this task
            $table->decimal('estimated_hours', 8, 2)->default(0);

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('proposal_activity_id');
        });

        // ── 7. Activity templates (system-wide reusable — admin-managed) ──────
        Schema::create('activity_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();

            // Optional: tie template to a specific work type for smart auto-suggestion
            $table->foreignId('work_type_id')->nullable()
                ->constrained('work_types')->nullOnDelete();

            $table->foreignId('project_manager_id')->nullable()
                ->constrained('users')->nullOnDelete();

            // Denormalized total budgeted hours (sum of all activities)
            $table->decimal('total_budgeted_hours', 10, 2)->default(0);

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('work_type_id');
        });

        // ── 8. Template deliverables ───────────────────────────────────────────
        Schema::create('activity_template_deliverables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_template_id')
                ->constrained('activity_templates')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('activity_template_id');
        });

        // ── 9. Template activities ─────────────────────────────────────────────
        Schema::create('activity_template_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_template_deliverable_id')
                ->constrained('activity_template_deliverables')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('relative_start_day')->nullable();
            $table->unsignedSmallInteger('relative_end_day')->nullable();
            $table->string('assigned_role', 100)->nullable();
            $table->decimal('budgeted_hours', 8, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('activity_template_deliverable_id');
        });

        // ── 10. Template tasks ─────────────────────────────────────────────────
        Schema::create('activity_template_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_template_activity_id')
                ->constrained('activity_template_activities')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('relative_due_day')->nullable();
            $table->string('assigned_role', 100)->nullable();
            $table->decimal('estimated_hours', 8, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('activity_template_activity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_template_tasks');
        Schema::dropIfExists('activity_template_activities');
        Schema::dropIfExists('activity_template_deliverables');
        Schema::dropIfExists('activity_templates');
        Schema::dropIfExists('proposal_tasks');
        Schema::dropIfExists('proposal_activities');
        Schema::dropIfExists('proposal_deliverables');
        Schema::dropIfExists('billing_schedule_periods');
        Schema::dropIfExists('billing_schedules');

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['contact_id']);
            $table->dropForeign(['project_type_id']);
            $table->dropForeign(['program_id']);
            $table->dropColumn([
                'contact_id', 'vendor_code', 'project_type_id',
                'contract_value', 'expenses_reserve', 'expiry_date',
                'program_id', 'google_doc_url', 'google_doc_synced_at',
            ]);
        });
    }
};
