<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voter_requests', function(Blueprint $table){
            $table->uuid('id')->primary();
            $table->string('request_number',30)->unique(); // new human-friendly sequential number
            $table->uuid('directory_id');
            $table->uuid('election_id');
            $table->uuid('request_type_id');
            $table->decimal('amount',12,2)->nullable();
            $table->text('note')->nullable();
            $table->string('status',30)->default('pending'); // added status column
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('directory_id')->references('id')->on('directories')->cascadeOnDelete();
            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('request_type_id')->references('id')->on('request_types');
            $table->index(['directory_id','election_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_requests');
    }
};
