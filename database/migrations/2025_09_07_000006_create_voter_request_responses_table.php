<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voter_request_responses', function(Blueprint $table){
            $table->uuid('id')->primary();
            $table->uuid('voter_request_id');
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response');
            $table->string('status_after',30)->nullable(); // snapshot of new status (e.g., approved, rejected, fulfilled)
            $table->timestamps();

            $table->foreign('voter_request_id')->references('id')->on('voter_requests')->cascadeOnDelete();
            $table->index(['voter_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_request_responses');
    }
};
