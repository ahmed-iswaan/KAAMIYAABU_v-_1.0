<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('number')->unique(); // added sequential human readable number
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('type')->default('other'); // form_fill, pickup, dropoff, other
            $table->string('status')->default('pending'); // pending, follow_up, completed
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->uuid('form_id')->nullable();
            $table->foreign('form_id')->references('id')->on('forms')->nullOnDelete();
            $table->uuid('directory_id')->nullable();
            $table->foreign('directory_id')->references('id')->on('directories')->nullOnDelete();
            $table->uuid('election_id')->nullable();
            $table->foreign('election_id')->references('id')->on('elections')->nullOnDelete();
            $table->dateTime('due_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['status']);
            $table->index(['priority']);
            $table->index(['due_at']);
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->uuid('task_id');
            $table->foreign('task_id')->references('id')->on('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['task_id','user_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('tasks');
    }
};
