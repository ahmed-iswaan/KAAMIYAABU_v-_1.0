<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('generated_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 80);

            $table->string('status', 30)->default('queued'); // queued|running|completed|failed
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->string('filename')->nullable();
            $table->string('disk')->default('local');
            $table->string('path')->nullable();

            $table->json('params')->nullable();
            $table->text('error')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_reports');
    }
};
