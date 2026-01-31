<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('call_center_forms', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('election_id');
            $table->uuid('directory_id');

            // Q1
            $table->string('q1_performance')->nullable();
            // kamudhey | kamunudhey | neyngey | mixed

            // Q2 (only if q1 != kamudhey)
            $table->text('q2_reason')->nullable();

            // Q3
            $table->string('q3_support')->nullable();
            // aanekey | noonekay | neyngey

            // Q4
            $table->string('q4_voting_area')->nullable();
            // male | vilimale | hulhumale_phase1 | hulhumale_phase2 | other | unknown
            $table->string('q4_other_text')->nullable();

            // Q5 (only if q4 is Male/Vilimale/Hulhumale)
            $table->string('q5_help_needed')->nullable();
            // yes | no | maybe

            // Q6
            $table->text('q6_message_to_mayor')->nullable();

            // Audit
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

            $table->unique(['election_id', 'directory_id']);

            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('directory_id')->references('id')->on('directories')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['election_id']);
            $table->index(['directory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('call_center_forms');
    }
};
