<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proposal Rate Schedules — per-engagement billing rate overrides.
 *
 * Rate Resolution Hierarchy (highest priority wins):
 *   1. proposal_rate_schedules (per-proposal, per-role rate)
 *   2. schedule_of_fees (global admin-configured rate per role)
 *
 * This allows each proposal/project to have negotiated rates that differ
 * from firm defaults (e.g., a government client at lower billing rates,
 * or a premium client at principal-level rates for all staff).
 *
 * scope:
 *   'role'      — rate applies to a specific staff role (e.g., "Principal")
 *   'work_type' — rate applies to a work type category
 *   'phase'     — rate applies to a specific AIA phase code (e.g., "CA")
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_rate_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained('proposals')->cascadeOnDelete();

            // What this rate applies to.
            $table->string('scope', 20)->default('role');
            // Values: role | work_type | phase

            // Scope reference: role name, work_type_id (as string), or phase code.
            // Stored as string for flexibility across scope types.
            $table->string('scope_value', 100);
            // Examples: "Principal", "3" (work_type_id), "CA"

            // The negotiated billing rate for this scope on this proposal.
            $table->decimal('hourly_rate', 10, 2);

            // Optional: fixed fee amount for this scope (used for per_milestone billing)
            $table->decimal('fixed_amount', 14, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('proposal_id');
            $table->unique(['proposal_id', 'scope', 'scope_value']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposal_rate_schedules');
    }
};
