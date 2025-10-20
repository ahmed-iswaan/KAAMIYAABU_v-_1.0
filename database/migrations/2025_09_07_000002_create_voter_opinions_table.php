<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voter_opinions', function(Blueprint $table){
            $table->uuid('id')->primary();
            $table->uuid('directory_id'); // voter
            $table->uuid('election_id');
            $table->uuid('opinion_type_id');
            $table->tinyInteger('rating')->nullable(); // 1-5 or null
            $table->string('status',20)->default('follow_up'); // status is now REQUIRED, default follow_up
            $table->text('note')->nullable();
            $table->foreignId('taken_by')->nullable()->constrained('users')->nullOnDelete(); // changed from uuid to foreignId (unsignedBigInteger) to match users.id (bigint)
            $table->timestamps();

            $table->foreign('directory_id')->references('id')->on('directories')->cascadeOnDelete();
            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('opinion_type_id')->references('id')->on('opinion_types');
            $table->index(['directory_id','election_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_opinions');
    }
};
