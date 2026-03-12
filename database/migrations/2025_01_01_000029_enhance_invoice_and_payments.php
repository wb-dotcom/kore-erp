<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Enhance invoices table ─────────────────────────────────────────────
        Schema::table('invoices', function (Blueprint $table) {
            $table->unsignedBigInteger('proposal_id')->nullable()->after('id');
            $table->unsignedBigInteger('billing_schedule_period_id')->nullable()->after('proposal_id');
            $table->string('invoice_type', 30)->default('manual')->after('status'); // manual|fixed_auto|tm_auto
            $table->decimal('balance_due', 15, 2)->default(0)->after('paid_amount');

            $table->foreign('proposal_id')->references('id')->on('proposals')->nullOnDelete();
            $table->foreign('billing_schedule_period_id')->references('id')->on('billing_schedule_periods')->nullOnDelete();
        });

        // ── Enhance invoice_items table ────────────────────────────────────────
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('deliverable_id');
            $table->string('role_name', 100)->nullable()->after('user_id');
            $table->decimal('hours', 8, 2)->nullable()->after('role_name');
            $table->decimal('rate', 10, 2)->nullable()->after('hours');
            $table->json('timesheet_entry_ids')->nullable()->after('rate');

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        // ── Create invoice_payments table ──────────────────────────────────────
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method', 50)->default('bank_transfer'); // check|wire|ach|credit_card|bank_transfer|other
            $table->string('reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('recorded_by')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
            $table->foreign('recorded_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'role_name', 'hours', 'rate', 'timesheet_entry_ids']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['proposal_id']);
            $table->dropForeign(['billing_schedule_period_id']);
            $table->dropColumn(['proposal_id', 'billing_schedule_period_id', 'invoice_type', 'balance_due']);
        });
    }
};
