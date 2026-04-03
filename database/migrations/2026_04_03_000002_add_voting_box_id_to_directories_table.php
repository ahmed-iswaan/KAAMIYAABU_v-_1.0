<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->uuid('voting_box_id')->nullable()->after('sub_consite_id');

            $table->foreign('voting_box_id')
                ->references('id')
                ->on('voting_boxes')
                ->nullOnDelete();

            $table->index('voting_box_id');
        });
    }

    public function down(): void
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->dropForeign(['voting_box_id']);
            $table->dropIndex(['voting_box_id']);
            $table->dropColumn('voting_box_id');
        });
    }
};
