<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique();
            $table->string('description', 255)->nullable();
            $table->timestamps();
        });

        // Seed the three default roles required by the auth middleware.
        DB::table('roles')->insert([
            ['name' => 'Admin',    'description' => 'Full system access',             'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Manager',  'description' => 'Project and team management',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Employee', 'description' => 'Standard employee access',       'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
