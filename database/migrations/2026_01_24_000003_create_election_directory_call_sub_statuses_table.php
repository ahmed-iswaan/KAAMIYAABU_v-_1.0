<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('election_directory_call_sub_statuses')) {
            return;
        }

        Schema::create('election_directory_call_sub_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('election_id');
            $table->uuid('directory_id');

            // attempt: 1..4
            $table->unsignedTinyInteger('attempt');

            // Uses existing Sub Status Types
            $table->uuid('sub_status_id')->nullable();
            $table->text('notes')->nullable();

            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['election_id', 'directory_id', 'attempt']);

            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('directory_id')->references('id')->on('directories')->cascadeOnDelete();
            $table->foreign('sub_status_id')->references('id')->on('sub_statuses')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['election_id', 'directory_id']);
            $table->index(['election_id', 'sub_status_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_directory_call_sub_statuses');
    }
};
