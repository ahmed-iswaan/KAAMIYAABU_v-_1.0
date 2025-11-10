<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if(Schema::hasTable('event_logs') && Schema::hasTable('tasks')){
            Schema::table('event_logs', function(Blueprint $table){
                if(!Schema::hasColumn('event_logs','task_id')){
                    $table->uuid('task_id')->nullable()->after('user_id');
                    // Use set null on delete only if tasks table exists
                    $table->foreign('task_id')->references('id')->on('tasks')->nullOnDelete();
                    $table->index('task_id');
                }
            });
        }
    }
    public function down(): void
    {
        if(Schema::hasTable('event_logs')){
            Schema::table('event_logs', function(Blueprint $table){
                if(Schema::hasColumn('event_logs','task_id')){
                    $table->dropForeign(['task_id']);
                    $table->dropIndex(['task_id']);
                    $table->dropColumn('task_id');
                }
            });
        }
    }
};
