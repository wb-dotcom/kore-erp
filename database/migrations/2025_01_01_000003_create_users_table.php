<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->unique();
            $table->string('password');
            $table->foreignId('role_id')->default(3)->constrained('roles');
            $table->string('avatar', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('department', 100)->nullable();
            $table->date('hire_date')->nullable();

            // ── Phase 1: Capacity planning fields ────────────────────────────
            // Contracted hours per week; used to compute available capacity.
            $table->unsignedSmallInteger('standard_weekly_hours')->default(40);

            // Internal fully-loaded cost rate (salary ÷ hours + overhead).
            // If null, falls back to schedule_of_fees for the user's role.
            $table->decimal('hourly_cost', 10, 2)->nullable();

            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamp('last_login')->nullable();
            $table->timestamps();

            $table->index('role_id');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
