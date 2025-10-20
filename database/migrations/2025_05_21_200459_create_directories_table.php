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
        Schema::create('directories', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Basic Info
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('profile_picture')->nullable();

            // Identification (replaced registration_number with id_card_number)
            $table->string('id_card_number')->unique()->nullable();
            // Removed: directory_type_id, registration_type_id, gst_number

            // Individual-specific fields
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('death_date')->nullable();

            // Contact Info (phone changed to json array 'phones')
            $table->json('phones')->nullable(); // Store multiple phone numbers as JSON array
            $table->string('email')->unique()->nullable();
            $table->string('website')->nullable();

            // Original (permanent / base) location references
            $table->uuid('country_id')->nullable();
            $table->uuid('island_id')->nullable();
            $table->string('address')->nullable();
            $table->string('street_address')->nullable();
            $table->uuid('properties_id')->nullable();

            // Current (dynamic) location references (new)
            $table->uuid('current_country_id')->nullable();
            $table->uuid('current_island_id')->nullable();
            $table->string('current_address')->nullable();
            $table->string('current_street_address')->nullable();
            $table->uuid('current_properties_id')->nullable();

            // Party affiliations (replaced JSON party_ids with single party_id FK)
            $table->uuid('party_id')->nullable();

            $table->string('status')->default('Active');

            // Removed: credit_balance, location_type

            // Timestamps
            $table->timestamps();

            // Foreign Keys (only for the kept / new location references)
            $table->foreign('country_id')
                  ->references('id')->on('countries')
                  ->cascadeOnDelete();
            $table->foreign('island_id')
                  ->references('id')->on('islands')
                  ->cascadeOnDelete();
            $table->foreign('properties_id')
                  ->references('id')->on('properties')
                  ->cascadeOnDelete();

            $table->foreign('current_country_id')
                  ->references('id')->on('countries')
                  ->cascadeOnDelete();
            $table->foreign('current_island_id')
                  ->references('id')->on('islands')
                  ->cascadeOnDelete();
            $table->foreign('current_properties_id')
                  ->references('id')->on('properties')
                  ->cascadeOnDelete();

            $table->foreign('party_id')
                  ->references('id')->on('parties')
                  ->nullOnDelete();

            // New foreign key for sub_consite
            $table->uuid('sub_consite_id')->nullable();
            $table->foreign('sub_consite_id')
                  ->references('id')->on('sub_consites')
                  ->nullOnDelete();

            // Indexes
            $table->index('country_id');
            $table->index('island_id');
            $table->index('current_country_id');
            $table->index('current_island_id');
            $table->index('status');
            $table->index('party_id');
            $table->index('sub_consite_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directories');
    }
};
