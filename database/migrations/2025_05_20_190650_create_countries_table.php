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
        Schema::create('countries', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name')->unique();
        $table->string('iso_codes', 3)->unique();      // ISO 3166-1 alpha-3
        $table->string('country_code', 4)->unique();   // Numeric code as string
        $table->string('dialing_code')->nullable(); 
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
