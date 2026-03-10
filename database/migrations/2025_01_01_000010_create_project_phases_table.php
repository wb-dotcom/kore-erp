<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PHASE 1 — Project Phases (AIA architectural stages).
 *
 * Standard AIA phase codes:
 *   PD  – Pre-Design / Programming
 *   SD  – Schematic Design
 *   DD  – Design Development
 *   CD  – Construction Documents
 *   BID – Bidding & Negotiation
 *   CA  – Construction Administration
 *   PO  – Post-Occupancy
 *
 * Phases are free-form per project (not enforced from a template table)
 * so firms can add custom phases like "Site Assessment" or "Permit Application".
 *
 * Budget Variance Formula:
 *   fixed_fee − SUM(timesheet_entries.hours × effective_billing_rate)
 *   where effective_billing_rate comes from proposal_rate_schedules (priority)
 *   or schedule_of_fees (fallback).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();

            $table->string('name');
            $table->string('code', 10)->nullable();
            // Convention: PD | SD | DD | CD | BID | CA | PO (or custom)

            $table->unsignedSmallInteger('phase_order')->default(0);

            // ── Fee & Budget ──────────────────────────────────────────────────
            $table->decimal('fixed_fee', 14, 2)->default(0);
            // The contracted dollar amount for this phase.

            $table->decimal('fee_percentage', 5, 2)->default(0);
            // This phase's fee as % of project total_budget. Stored (not computed)
            // so it survives budget revisions without silent drift.

            $table->decimal('estimated_hours', 10, 2)->default(0);
            // Labor hours budgeted. Compared against SUM(timesheet_entries.hours).

            // ── Completion ────────────────────────────────────────────────────
            $table->decimal('percent_complete', 5, 2)->default(0);
            // 0.00 – 100.00. PM updates manually; Phase 3 AI can suggest updates.

            // ── Status & Dates ────────────────────────────────────────────────
            $table->string('status', 20)->default('not_started');
            // Values: not_started | in_progress | on_hold | completed

            $table->date('planned_start_date')->nullable();
            $table->date('planned_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'code']);
            $table->index(['project_id', 'phase_order']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_phases');
    }
};
