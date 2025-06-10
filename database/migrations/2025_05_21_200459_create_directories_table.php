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

            // Entity Type and Registration
            $table->uuid('directory_type_id');
            $table->uuid('registration_type_id')->nullable();
            $table->string('registration_number')->unique()->nullable();

            // Individual-specific fields
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();

            // Contact Info
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Location references
            $table->uuid('country_id')->nullable();
            $table->uuid('island_id')->nullable();
            $table->string('address')->nullable();

            // Residence type: inland vs outer islander
            $table->enum('location_type', ['inland', 'outer_islander'])->default('inland');

            // Timestamps
            $table->timestamps();

            // Foreign Keys
            $table->foreign('directory_type_id')
                  ->references('id')->on('directory_types')
                  ->cascadeOnDelete();
            $table->foreign('registration_type_id')
                  ->references('id')->on('registration_types')
                  ->nullOnDelete();
            $table->foreign('country_id')
                  ->references('id')->on('countries')
                  ->cascadeOnDelete();
            $table->foreign('island_id')
                  ->references('id')->on('islands')
                  ->cascadeOnDelete();

            // Indexes
            $table->index('directory_type_id');
            $table->index('registration_type_id');
            $table->index('country_id');
            $table->index('island_id');
            $table->index('location_type');
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
