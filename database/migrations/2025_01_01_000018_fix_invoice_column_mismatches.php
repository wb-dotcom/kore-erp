<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Fix column mismatches between the original migrations and the application code.
 *
 * invoices:
 *   - Add company_id (code references invoices.company_id directly)
 *   - Rename paid_date → paid_at (controller/model use paid_at as datetime)
 *   - Add paid_amount (used by markPaid() and model $fillable)
 *   - Add sent_at (used by sendEmail() and model $fillable)
 *
 * invoice_items:
 *   - Rename amount → line_total (model and controller use line_total)
 *   - Rename project_phase_id → deliverable_id (model uses deliverable_id)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('project_id')->constrained('companies')->nullOnDelete();
            $table->timestamp('sent_at')->nullable()->after('paid_date');
            $table->timestamp('paid_at')->nullable()->after('sent_at');
            $table->decimal('paid_amount', 14, 2)->default(0)->after('total');
        });

        // Copy existing paid_date values into paid_at
        DB::statement('UPDATE invoices SET paid_at = paid_date WHERE paid_date IS NOT NULL');

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->renameColumn('amount', 'line_total');
            $table->renameColumn('project_phase_id', 'deliverable_id');
        });

        // Drop the old paid_date column after data is migrated
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('paid_date');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'sent_at', 'paid_at', 'paid_amount']);
            $table->date('paid_date')->nullable();
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->renameColumn('line_total', 'amount');
            $table->renameColumn('deliverable_id', 'project_phase_id');
        });
    }
};
