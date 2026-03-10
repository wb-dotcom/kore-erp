<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1.5: Proposal Builder — fee worksheet line items + scope fields.
 *
 * proposal_line_items
 *   One row per billable unit in the proposal: a role performing a deliverable
 *   within a phase. Hours × rate = amount. The line items roll up to
 *   produce the total fee for each AIA phase and the overall proposal total.
 *
 *   Rate resolution at line-item creation time:
 *     1. proposal_rate_schedules for this proposal + role (negotiated rate)
 *     2. schedule_of_fees for this role (global firm default)
 *   Rate is snapshotted into the row so future fee-schedule changes don't
 *   silently alter historical proposals.
 *
 * proposals (new columns):
 *   executive_summary — short paragraph for the cover letter
 *   scope_of_work     — rich text (stored as HTML from Tiptap on the frontend)
 *   terms_and_conditions — legal boilerplate, editable per proposal
 *   total_fee         — denormalised SUM of line item amounts for quick display
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add scope and summary fields to proposals
        Schema::table('proposals', function (Blueprint $table) {
            $table->text('executive_summary')->nullable()->after('description');
            $table->text('scope_of_work')->nullable()->after('executive_summary');
            $table->text('terms_and_conditions')->nullable()->after('scope_of_work');
            // Denormalised total — recomputed by ProposalFeeCalculator after any line change.
            $table->decimal('total_fee', 14, 2)->default(0)->after('terms_and_conditions');
        });

        Schema::create('proposal_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();

            // AIA phase code (SD, DD, CD, CA, etc.) or custom label
            $table->string('phase_code', 20);

            // Human-readable phase label (e.g. "Schematic Design")
            $table->string('phase_label', 100)->nullable();

            // What is being delivered in this line
            $table->string('deliverable', 500);

            // Optional milestone name this line is tied to
            $table->string('milestone', 200)->nullable();

            // Staff role performing the work (links to schedule_of_fees.role_name)
            $table->string('role_name', 100);

            // Budgeted hours for this role on this deliverable
            $table->decimal('hours', 10, 2)->default(0);

            // Billing rate at time of proposal creation (snapshotted from fee schedule)
            $table->decimal('rate', 10, 2)->default(0);

            // Computed amount = hours × rate. Stored for fast aggregation.
            // Override-able: if amount_override is set, that value is used instead.
            $table->decimal('amount', 14, 2)->default(0);

            // Optional manual override of computed amount (e.g. fixed sub-contracted item)
            $table->decimal('amount_override', 14, 2)->nullable();

            // Controls sort order within a phase group
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['proposal_id', 'phase_code']);
            $table->index('proposal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_line_items');

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['executive_summary', 'scope_of_work', 'terms_and_conditions', 'total_fee']);
        });
    }
};
