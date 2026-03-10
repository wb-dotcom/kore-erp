<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Work types, proposal statuses, and proposals with full billing architecture.
 *
 * Billing Architecture (clarified in Phase 1):
 *   billing_type:  How the client is charged (fixed, T&M, per milestone, etc.)
 *   billing_cycle: When invoices are issued (monthly, on milestone, on phase, etc.)
 *
 * Rate flow:
 *   Admin sets global schedule_of_fees → Proposal defines per-engagement rates
 *   (proposal_rate_schedules) → Project phases reference those rates for burn calcs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('proposal_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('proposal_number');
            $table->string('title');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->nullOnDelete();
            $table->foreignId('work_type_id')->nullable()->constrained('work_types')->nullOnDelete();
            $table->foreignId('account_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('status_id')->constrained('proposal_statuses');
            $table->string('po_number', 100)->nullable();
            $table->text('description')->nullable();
            $table->date('submitted_date')->nullable();
            $table->date('approved_date')->nullable();

            // ── Phase 1: Billing architecture ────────────────────────────────
            // How the client is billed for this engagement.
            $table->string('billing_type', 30)->default('fixed');
            // Values: fixed | time_and_material | per_milestone | per_deliverable | hybrid

            // When invoices are issued.
            $table->string('billing_cycle', 30)->default('phase_completion');
            // Values: monthly | milestone_completion | deliverable_completion
            //         | phase_completion | project_completion | custom

            // Net payment terms (days).
            $table->unsignedSmallInteger('payment_terms_days')->default(30);

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['year', 'proposal_number']);
            $table->index('company_id');
            $table->index('status_id');
            $table->index('year');
        });

        // ── Seed lookup data ──────────────────────────────────────────────────
        DB::table('work_types')->insert(array_map(fn ($n) => ['name' => $n], [
            'Architecture & Interior Group',
            'Building Technology Services',
            'Civil Engineering',
            'Construction Administration',
            'Interior Design',
            'Landscape Architecture',
            'Project Management',
            'Urban Planning',
        ]));

        DB::table('proposal_statuses')->insert(array_map(fn ($n) => ['name' => $n], [
            'Draft', 'Submitted', 'Under Review', 'Approved', 'Rejected', 'Withdrawn',
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
        Schema::dropIfExists('proposal_statuses');
        Schema::dropIfExists('work_types');
    }
};
