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
        Schema::create('pending_telegram_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id');
            $table->text('message');
            $table->boolean('is_sent')->default(false);
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_telegram_notifications');
    }
};
