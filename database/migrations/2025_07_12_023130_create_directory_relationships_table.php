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
      Schema::create('directory_relationships', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Linked directory (the one this director/delegate is associated with)
            $table->uuid('directory_id');
            $table->foreign('directory_id')->references('id')->on('directories')->onDelete('cascade');

            // Director or delegate
            $table->uuid('linked_directory_id'); 
            $table->foreign('linked_directory_id')->references('id')->on('directories')->onDelete('cascade');

            $table->string('link_type'); // e.g., director, delegate
            $table->string('designation')->nullable(); // title/role
            $table->json('permissions')->nullable(); // JSON permissions
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->text('remark')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('directory_relationships');
    }
};
