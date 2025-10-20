<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sub_consites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('consite_id');
            $table->string('code')->unique();
            $table->string('name');
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->foreign('consite_id')
                  ->references('id')->on('consites')
                  ->cascadeOnDelete();

            $table->index('consite_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sub_consites');
    }
};
