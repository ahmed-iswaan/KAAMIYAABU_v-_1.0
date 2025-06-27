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
         Schema::create('waste_management_registers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique();           // e.g., WM-MUL/2025/0001
            $table->string('register_number')->unique();
            $table->uuid('property_id');
            $table->uuid('directories_id');
            $table->uuid('fk_waste_price_list');
            $table->string('floor')->nullable();
            $table->string('block_count')->nullable();
            $table->string('applicant_is'); // e.g., owner, renter
            $table->enum('status', ['pending', 'active', 'inactive', 'terminated'])
                  ->default('pending');
            $table->text('status_change_note')->nullable();
            $table->timestamps();

            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('directories_id')->references('id')->on('directories')->onDelete('cascade');
            $table->foreign('fk_waste_price_list')->references('id')->on('waste_collection_price_lists')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waste_management_registers');
    }
};
