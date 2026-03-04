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
        Schema::table('directories', function (Blueprint $table) {
            $table->string('past_atoll')->nullable()->after('block');
            $table->string('past_island')->nullable()->after('past_atoll');
            $table->string('past_ward')->nullable()->after('past_island');
            $table->string('past_address1')->nullable()->after('past_ward');
            $table->string('past_address2')->nullable()->after('past_address1');
            $table->string('prev_constituency')->nullable()->after('past_address2');

            $table->index('past_atoll');
            $table->index('past_island');
            $table->index('past_ward');
            $table->index('prev_constituency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->dropIndex(['past_atoll']);
            $table->dropIndex(['past_island']);
            $table->dropIndex(['past_ward']);
            $table->dropIndex(['prev_constituency']);

            $table->dropColumn([
                'past_atoll',
                'past_island',
                'past_ward',
                'past_address1',
                'past_address2',
                'prev_constituency',
            ]);
        });
    }
};
