<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('Active');
            $table->timestamps();
            $table->index('status');
            $table->index(['start_date','end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
