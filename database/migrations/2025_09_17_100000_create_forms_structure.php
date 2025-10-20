<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug')->unique();
            $table->string('language',5)->default('en'); // 'en' or 'dv'
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('draft'); // draft, published, archived
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('form_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_id');
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('form_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_id');
            $table->uuid('form_section_id')->nullable();
            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
            $table->foreign('form_section_id')->references('id')->on('form_sections')->cascadeOnDelete();
            $table->string('type'); // short_text, long_text, number, date, select, multiselect, radio, checkbox, yes_no, rating, file, matrix
            $table->string('code')->nullable(); // internal reference
            $table->string('question_text');
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable(); // e.g. {"min":2,"max":50}
            $table->json('meta')->nullable(); // extra config like rating scale, matrix headers etc.
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('form_question_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_question_id');
            $table->foreign('form_question_id')->references('id')->on('form_questions')->cascadeOnDelete();
            $table->string('value');
            $table->string('label');
            $table->unsignedInteger('position')->default(0);
            $table->json('meta')->nullable(); // e.g. color, score weight
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_question_options');
        Schema::dropIfExists('form_questions');
        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('forms');
    }
};
