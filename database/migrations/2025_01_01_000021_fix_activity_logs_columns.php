<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->string('module', 100)->nullable()->after('action');
            $table->unsignedBigInteger('reference_id')->nullable()->after('module');
            $table->string('details', 500)->nullable()->after('reference_id');
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn(['module', 'reference_id', 'details']);
        });
    }
};
