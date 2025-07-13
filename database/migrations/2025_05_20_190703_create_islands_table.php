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
        Schema::create('islands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('atoll_id');
            $table->string('name');
            $table->string('island_code')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->foreign('atoll_id')->references('id')->on('atolls')->cascadeOnDelete();
            $table->unique(['atoll_id','name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('islands');
    }
};
