<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voted_representatives', function (Blueprint $table) {
            $table->id();
            $table->uuid('election_id');
            $table->uuid('directory_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('voted_at')->nullable();
            $table->timestamps();

            $table->unique(['election_id','directory_id'], 'uniq_election_directory');

            // Foreign key constraints
            $table->foreign('election_id')->references('id')->on('elections')->onDelete('cascade');
            $table->foreign('directory_id')->references('id')->on('directories')->onDelete('cascade');

            $table->index('election_id');
            $table->index('directory_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voted_representatives');
    }
};
