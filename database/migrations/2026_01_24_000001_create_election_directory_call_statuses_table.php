<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('election_directory_call_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('election_id');
            $table->uuid('directory_id');

            $table->string('status'); // completed | in_progress | unreachable | wrong_number | callback | do_not_call | etc
            $table->text('notes')->nullable();

            // users.id is bigint in this project
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->unique(['election_id', 'directory_id']);

            $table->foreign('election_id')->references('id')->on('elections')->cascadeOnDelete();
            $table->foreign('directory_id')->references('id')->on('directories')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['election_id', 'status']);
            $table->index(['directory_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('election_directory_call_statuses');
    }
};
