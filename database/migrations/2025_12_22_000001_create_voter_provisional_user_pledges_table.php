<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voter_provisional_user_pledges', function (Blueprint $table) {
            $table->id();
            // UUIDs to match Directory/Election ids
            $table->uuid('election_id');
            $table->uuid('directory_id');
            // Users typically use big-increments id
            $table->foreignId('user_id');
            $table->enum('status', ['yes','no','neutral'])->nullable();
            $table->timestamps();

            // Constraints via indexes (no FK to avoid migration errors)
            $table->unique(['election_id','directory_id','user_id'], 'vpu_uniq_election_directory_user');
            $table->index('election_id');
            $table->index('directory_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_provisional_user_pledges');
    }
};
