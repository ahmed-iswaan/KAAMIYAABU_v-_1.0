<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
            $table->uuid('directory_id')->nullable();
            $table->foreign('directory_id')->references('id')->on('directories')->nullOnDelete();
            $table->uuid('election_id')->nullable();
            $table->foreign('election_id')->references('id')->on('elections')->nullOnDelete();
            $table->uuid('task_id')->nullable();
            $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
            $table->string('submission_uuid')->unique();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_agent_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('in_progress'); // in_progress, submitted, reviewed, rejected, archived
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('form_submission_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_submission_id');
            $table->foreign('form_submission_id')->references('id')->on('form_submissions')->cascadeOnDelete();
            $table->uuid('form_question_id');
            $table->foreign('form_question_id')->references('id')->on('form_questions')->cascadeOnDelete();
            $table->text('value_text')->nullable(); // for short/long text
            $table->text('value_text_dv')->nullable(); // Dhivehi answer if separated
            $table->json('value_json')->nullable(); // multi select, matrix etc.
            $table->string('value_number')->nullable(); // store numeric as string to avoid precision issues; cast in model
            $table->date('value_date')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submission_answers');
        Schema::dropIfExists('form_submissions');
    }
};
