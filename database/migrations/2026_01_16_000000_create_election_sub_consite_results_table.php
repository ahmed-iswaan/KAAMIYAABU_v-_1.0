<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_sub_consite_results', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('election_id');
            $table->uuid('sub_consite_id');

            $table->unsignedInteger('total_eligible_voters')->default(0);
            $table->unsignedInteger('yes_votes')->default(0);
            $table->unsignedInteger('no_votes')->default(0);
            $table->unsignedInteger('invalid_votes')->default(0);

            // users.id is BIGINT (not UUID)
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->unique(['election_id', 'sub_consite_id'], 'uniq_election_subconsite_result');

            $table->index(['election_id']);
            $table->index(['sub_consite_id']);

            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('sub_consite_id')->references('id')->on('sub_consites')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_sub_consite_results');
    }
};
