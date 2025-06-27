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
        Schema::create('waste_collection_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('property_id');
            $table->uuid('directories_id');
            $table->uuid('waste_management_register_id')->nullable();
            $table->unsignedBigInteger('driver_id')->nullable();
            $table->uuid('vehicle_id');

            $table->decimal('completed_latitude', 10, 7)->nullable();
            $table->decimal('completed_longitude', 10, 7)->nullable();

            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('note')->nullable();
            $table->unsignedInteger('index')->nullable();

            $table->decimal('total_collected', 10, 2)->nullable();
            $table->json('waste_data')->nullable();

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

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
        Schema::dropIfExists('waste_collection_tasks');
    }
};
