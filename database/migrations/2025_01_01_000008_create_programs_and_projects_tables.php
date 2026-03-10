<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Programs, project types/statuses, and projects.
 *
 * Hierarchy:
 *   Program (macro rollout) → Projects (individual sites) → ProjectPhases (AIA stages)
 *
 * Programs are the Kore ERP concept for a multi-site client rollout.
 * Example: "Ford Signature 2.0" program contains 12 dealership-site projects.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->boolean('is_billable')->default(true);
        });

        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
        });

        // ── PHASE 1: Programs ─────────────────────────────────────────────────
        Schema::create('programs', function (Blueprint $table) {
            $table->id();

            // Unique short code for the rollout — used in subject-line parsing (Phase 2).
            $table->string('code', 30)->unique();

            $table->string('name');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->text('description')->nullable();

            // Ceiling budget across ALL sites in this program.
            $table->decimal('global_budget', 14, 2)->default(0);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->string('status', 20)->default('active');
            // Values: active | on_hold | completed | cancelled

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('status');
        });

        // ── Projects (individual dealership sites) ────────────────────────────
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('project_number', 20)->unique();
            $table->string('title');
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('project_manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('project_type_id')->nullable()->constrained('project_types')->nullOnDelete();
            $table->foreignId('status_id')->constrained('project_statuses');
            $table->foreignId('proposal_id')->nullable()->constrained('proposals')->nullOnDelete();

            // Phase 1: Link to macro-level Program.
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('total_budget', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_id');
            $table->index('project_manager_id');
            $table->index('status_id');
            $table->index('program_id');
            $table->index('year');
        });

        // ── Seed lookup data ──────────────────────────────────────────────────
        DB::table('project_types')->insert([
            ['name' => 'Billable',     'is_billable' => true],
            ['name' => 'Non-Billable', 'is_billable' => false],
            ['name' => 'Internal',     'is_billable' => false],
        ]);

        DB::table('project_statuses')->insert(array_map(fn ($n) => ['name' => $n], [
            'Active', 'On Hold', 'Completed', 'Cancelled', 'Prospect',
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('programs');
        Schema::dropIfExists('project_statuses');
        Schema::dropIfExists('project_types');
    }
};
