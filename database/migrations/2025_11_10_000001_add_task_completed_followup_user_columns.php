<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if(Schema::hasTable('tasks')){
            Schema::table('tasks', function(Blueprint $table){
                if(!Schema::hasColumn('tasks','completed_by')){
                    $table->foreignId('completed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
                }
                if(!Schema::hasColumn('tasks','follow_up_by')){
                    $table->foreignId('follow_up_by')->nullable()->after('completed_by')->constrained('users')->nullOnDelete();
                }
            });
        }
    }
    public function down(): void
    {
        if(Schema::hasTable('tasks')){
            Schema::table('tasks', function(Blueprint $table){
                if(Schema::hasColumn('tasks','follow_up_by')){ $table->dropForeign(['follow_up_by']); $table->dropColumn('follow_up_by'); }
                if(Schema::hasColumn('tasks','completed_by')){ $table->dropForeign(['completed_by']); $table->dropColumn('completed_by'); }
            });
        }
    }
};
