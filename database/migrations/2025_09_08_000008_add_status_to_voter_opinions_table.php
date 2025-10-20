<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToVoterOpinionsTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('voter_opinions') && ! Schema::hasColumn('voter_opinions','status')) {
            Schema::table('voter_opinions', function(Blueprint $table){
                $table->string('status',20)->default('follow_up')->after('rating');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('voter_opinions') && Schema::hasColumn('voter_opinions','status')) {
            Schema::table('voter_opinions', function(Blueprint $table){
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            });
        }
    }
}
