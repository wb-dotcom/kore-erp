<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('address_line1', 255)->nullable()->after('website');
            $table->string('address_line2', 255)->nullable()->after('address_line1');
            $table->string('city', 100)->nullable()->after('address_line2');
            $table->string('state', 100)->nullable()->after('city');
            $table->string('zip', 20)->nullable()->after('state');
            $table->string('country', 100)->nullable()->after('zip');
        });

        // Migrate any existing data from the old 'address' column into address_line1
        DB::statement("UPDATE companies SET address_line1 = address WHERE address IS NOT NULL AND address != ''");

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('address', 500)->nullable()->after('website');
        });

        DB::statement("UPDATE companies SET address = address_line1 WHERE address_line1 IS NOT NULL");

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['address_line1', 'address_line2', 'city', 'state', 'zip', 'country']);
        });
    }
};
