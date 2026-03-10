<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * CRM tables: sectors, regions, contact_types, companies, contacts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sectors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('contact_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->foreignId('sector_id')->nullable()->constrained('sectors')->nullOnDelete();
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->string('website', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('address', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('sector_id');
            $table->index('region_id');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('contact_type_id')->nullable()->constrained('contact_types')->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('title', 100)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index('company_id');
            $table->index('contact_type_id');
        });

        // ── Seed lookup data ──────────────────────────────────────────────────
        DB::table('sectors')->insert(array_map(fn ($n) => ['name' => $n], [
            'Architecture', 'Automotive', 'Building Technology',
            'Commercial', 'Education', 'Government', 'Healthcare',
            'Hospitality', 'Industrial', 'Mixed Use', 'Residential', 'Retail',
        ]));

        DB::table('regions')->insert(array_map(fn ($n) => ['name' => $n], [
            'North America', 'Northeast', 'Southeast',
            'Midwest', 'Southwest', 'West Coast', 'International',
        ]));

        DB::table('contact_types')->insert(array_map(fn ($n) => ['name' => $n], [
            'Client', 'Prospect', 'Vendor', 'Partner', 'Contractor', 'Other',
        ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('contact_types');
        Schema::dropIfExists('regions');
        Schema::dropIfExists('sectors');
    }
};
