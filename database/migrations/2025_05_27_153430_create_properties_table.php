<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
        $table->uuid('id')->primary();
                $table->string('name');
                $table->string('register_number', 100)->unique();
                $table->uuid('property_type_id');
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->decimal('square_feet', 10, 2);
                $table->uuid('island_id');
                $table->uuid('ward_id')->nullable();
                $table->timestamps();

                $table
                    ->foreign('island_id')
                    ->references('id')
                    ->on('islands')
                    ->cascadeOnDelete();

                $table
                    ->foreign('ward_id')
                    ->references('id')
                    ->on('wards')
                    ->nullOnDelete();

                $table->foreign('property_type_id')
                  ->references('id')
                  ->on('property_types')
                  ->cascadeOnDelete();
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
