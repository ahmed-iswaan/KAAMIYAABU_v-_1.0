<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_phone_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('directory_id');
            $table->string('phone', 30);

            $table->string('status')->default('not_called');
            $table->text('notes')->nullable();
            $table->dateTime('last_called_at')->nullable();
            $table->foreignId('last_called_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->foreign('directory_id')->references('id')->on('directories')->cascadeOnDelete();
            $table->unique(['directory_id', 'phone']);
            $table->index(['directory_id']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_phone_statuses');
    }
};
