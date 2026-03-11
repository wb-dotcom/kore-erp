<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3: Proposal–Project Independence
 *
 * - Companies: add vendor_code (synced to proposal vendor_code field)
 * - Deliverables: add budget_hours, rate, deliverable_fee, billing_status, due_date
 * - Milestones:  add budget_hours, rate, deliverable_fee, billing_status
 * - Tasks:       add budget_hours, rate
 * - Seed "K5 Company" as the default non-billable project client
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add vendor_code to companies ───────────────────────────────────
        Schema::table('companies', function (Blueprint $table) {
            $table->string('vendor_code', 50)->nullable()->after('phone');
        });

        // ── 2. Add budget/billing fields to deliverables ──────────────────────
        Schema::table('deliverables', function (Blueprint $table) {
            $table->decimal('budget_hours', 10, 2)->default(0)->after('description');
            $table->decimal('rate', 10, 2)->nullable()->after('budget_hours');
            $table->decimal('deliverable_fee', 14, 2)->nullable()->after('rate');
            // pending | ready_to_bill | invoiced | paid
            $table->string('billing_status', 20)->default('pending')->after('deliverable_fee');
            $table->date('due_date')->nullable()->after('billing_status');
        });

        // ── 3. Add budget/billing fields to milestones ────────────────────────
        Schema::table('milestones', function (Blueprint $table) {
            $table->decimal('budget_hours', 10, 2)->default(0)->after('description');
            $table->decimal('rate', 10, 2)->nullable()->after('budget_hours');
            $table->decimal('deliverable_fee', 14, 2)->nullable()->after('rate');
            // pending | ready_to_bill | invoiced | paid
            $table->string('billing_status', 20)->default('pending')->after('deliverable_fee');
        });

        // ── 4. Add budget fields to tasks ─────────────────────────────────────
        Schema::table('tasks', function (Blueprint $table) {
            $table->decimal('budget_hours', 10, 2)->default(0)->after('description');
            $table->decimal('rate', 10, 2)->nullable()->after('budget_hours');
        });

        // ── 5. Seed K5 Company as default internal client ─────────────────────
        // Only insert if it doesn't already exist
        if (DB::table('companies')->where('name', 'K5 Company')->doesntExist()) {
            DB::table('companies')->insert([
                'name'       => 'K5 Company',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['budget_hours', 'rate']);
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->dropColumn(['budget_hours', 'rate', 'deliverable_fee', 'billing_status']);
        });

        Schema::table('deliverables', function (Blueprint $table) {
            $table->dropColumn(['budget_hours', 'rate', 'deliverable_fee', 'billing_status', 'due_date']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('vendor_code');
        });
    }
};
