<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Named Fee Schedules — admin-created rate tables selectable per proposal.
 *
 * fee_schedules: named containers (e.g. "Standard 2024", "Government Rate")
 *   Each proposal can reference one fee schedule.
 *   schedule_of_fees rows get a fee_schedule_id so they belong to a named schedule.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Add fee_schedule_id to existing schedule_of_fees rows
        Schema::table('schedule_of_fees', function (Blueprint $table) {
            $table->foreignId('fee_schedule_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('fee_schedules')
                  ->nullOnDelete();
            $table->index('fee_schedule_id');
        });

        // Add fee_schedule_id to proposals so each proposal references a schedule
        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('fee_schedule_id')
                  ->nullable()
                  ->after('program_id')
                  ->constrained('fee_schedules')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['fee_schedule_id']);
            $table->dropColumn('fee_schedule_id');
        });

        Schema::table('schedule_of_fees', function (Blueprint $table) {
            $table->dropForeign(['fee_schedule_id']);
            $table->dropColumn('fee_schedule_id');
        });

        Schema::dropIfExists('fee_schedules');
    }
};
