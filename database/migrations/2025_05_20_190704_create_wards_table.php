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
        Schema::create('wards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('island_id');
            $table->string('name');
            $table->timestamps();

            $table
                ->foreign('island_id')
                ->references('id')
                ->on('islands')
                ->cascadeOnDelete();

            $table->unique(['island_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wards');
    }
};
