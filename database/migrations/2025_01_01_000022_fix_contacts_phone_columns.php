<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('business_phone', 50)->nullable()->after('email');
            $table->string('mobile_phone', 50)->nullable()->after('business_phone');
        });

        // Migrate any existing data from the old 'phone' column into business_phone
        DB::statement("UPDATE contacts SET business_phone = phone WHERE phone IS NOT NULL AND phone != ''");

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('phone', 30)->nullable()->after('email');
        });

        DB::statement("UPDATE contacts SET phone = business_phone WHERE business_phone IS NOT NULL");

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['business_phone', 'mobile_phone']);
        });
    }
};
