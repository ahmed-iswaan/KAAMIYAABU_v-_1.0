<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
 public function up()
    {
        Schema::create('waste_collection_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Related data
            $table->uuid('property_id');
            $table->uuid('directories_id');
            $table->uuid('waste_management_register_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->uuid('vehicle_id');

            // Scheduling metadata
            $table->date('start_date');                    // When the schedule began
            $table->date('next_collection_date');          // When the next task should be created
            $table->enum('recurrence', ['daily', 'weekly', 'monthly']);
            $table->unsignedInteger('total_cycles')->nullable(); // How many tasks to create in total
            $table->unsignedInteger('generated_count')->default(0);
            $table->boolean('is_active')->default(true);

            // Waste info to auto-populate
            $table->json('waste_data')->nullable();       // Default waste types/quantities
            $table->text('note')->nullable();             // Optional note
            $table->timestamps();

            // Foreign keys
            $table->foreign('property_id')->references('id')->on('properties')->onDelete('cascade');
            $table->foreign('directories_id')->references('id')->on('directories')->onDelete('cascade');
            $table->foreign('driver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->foreign('waste_management_register_id')
                  ->references('id')
                  ->on('waste_management_registers')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('waste_collection_schedules');
    }
};
