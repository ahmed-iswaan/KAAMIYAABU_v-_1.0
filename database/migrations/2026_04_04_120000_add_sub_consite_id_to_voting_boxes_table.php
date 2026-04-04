<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voting_boxes', function (Blueprint $table) {
            // sub_consites.id is UUID in this project
            $table->uuid('sub_consite_id')->nullable()->after('name');

            $table->foreign('sub_consite_id')
                ->references('id')
                ->on('sub_consites')
                ->nullOnDelete();

            $table->index('sub_consite_id');
        });
    }

    public function down(): void
    {
        Schema::table('voting_boxes', function (Blueprint $table) {
            $table->dropForeign(['sub_consite_id']);
            $table->dropIndex(['sub_consite_id']);
            $table->dropColumn('sub_consite_id');
        });
    }
};
