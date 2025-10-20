<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('voter_notes', function(Blueprint $table){
            $table->uuid('id')->primary();
            $table->uuid('directory_id');
            $table->uuid('election_id');
            $table->text('note'); // required
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('directory_id')->references('id')->on('directories')->cascadeOnDelete();
            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->index(['directory_id','election_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voter_notes');
    }
};
