<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('election_id');
            $table->uuid('sub_consite_id');
            $table->string('status')->default('Active');
            $table->timestamps();

            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('sub_consite_id')->references('id')->on('sub_consites')->cascadeOnDelete();

            $table->unique(['election_id','sub_consite_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participants');
    }
};
