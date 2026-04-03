<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_voting_boxes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->uuid('voting_box_id');
            $table->foreign('voting_box_id')
                ->references('id')
                ->on('voting_boxes')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'voting_box_id']);
            $table->index('voting_box_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_voting_boxes');
    }
};
