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
        Schema::create('election_results', function (Blueprint $table) {
            $table->id();

            // elections.id is UUID in this project
            $table->uuid('election_id');

            // voting_boxes.id is UUID in this project
            $table->uuid('voting_box_id');

            $table->unsignedInteger('candidate_1_votes')->default(0);
            $table->unsignedInteger('candidate_2_votes')->default(0);
            $table->unsignedInteger('candidate_3_votes')->default(0);
            $table->unsignedInteger('candidate_4_votes')->default(0);
            $table->unsignedInteger('candidate_5_votes')->default(0);

            $table->unsignedInteger('invalid_votes')->default(0);

            $table->dateTime('result_datetime')->nullable();

            $table->timestamps();

            $table->unique(['election_id', 'voting_box_id']);

            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('voting_box_id')->references('id')->on('voting_boxes')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('election_results');
    }
};
