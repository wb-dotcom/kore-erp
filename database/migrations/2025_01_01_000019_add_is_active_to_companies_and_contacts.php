<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The companies and contacts tables were created without an is_active flag,
 * but all application code (controllers, models, middleware) expects it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('notes');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
