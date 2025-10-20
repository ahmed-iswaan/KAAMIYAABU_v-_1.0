<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voter_pledges', function(Blueprint $table){
            $table->uuid('id')->primary();
            $table->uuid('directory_id');
            $table->uuid('election_id');
            $table->string('type',20); // provisional | final
            $table->string('status',20); // strong_yes, yes, neutral, no, strong_no
            $table->text('note')->nullable(); // added note field
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('directory_id')->references('id')->on('directories')->cascadeOnDelete();
            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->unique(['directory_id','election_id','type']);
            $table->index(['election_id','type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_pledges');
    }
};
