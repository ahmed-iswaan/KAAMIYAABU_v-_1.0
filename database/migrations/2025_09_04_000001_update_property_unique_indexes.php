<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Drop existing single-column unique indexes (if they still exist)
            try { $table->dropUnique('properties_number_unique'); } catch (\Throwable $e) {}
            try { $table->dropUnique('properties_register_number_unique'); } catch (\Throwable $e) {}
        });

        Schema::table('properties', function (Blueprint $table) {
            // Add composite uniques scoped per island
            $table->unique(['island_id','number'], 'properties_island_id_number_unique');
            $table->unique(['island_id','register_number'], 'properties_island_id_register_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            try { $table->dropUnique('properties_island_id_number_unique'); } catch (\Throwable $e) {}
            try { $table->dropUnique('properties_island_id_register_number_unique'); } catch (\Throwable $e) {}
        });

        Schema::table('properties', function (Blueprint $table) {
            // Recreate original single-column unique indexes
            $table->unique('number');
            $table->unique('register_number');
        });
    }
};
