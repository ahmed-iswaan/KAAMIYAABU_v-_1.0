<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if(!Schema::hasColumn('tasks','deleted')){
                $table->boolean('deleted')->default(false)->after('sub_status_id')->index();
            }
            if(!Schema::hasColumn('tasks','deleted_at')){
                $table->timestamp('deleted_at')->nullable()->after('follow_up_date')->index();
            }
            if(!Schema::hasColumn('tasks','deleted_by')){
                $table->unsignedBigInteger('deleted_by')->nullable()->after('deleted_at')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            if(Schema::hasColumn('tasks','deleted_by')){ $table->dropColumn('deleted_by'); }
            if(Schema::hasColumn('tasks','deleted_at')){ $table->dropColumn('deleted_at'); }
            // keep deleted column if previously added elsewhere? We drop it here for clean rollback
            if(Schema::hasColumn('tasks','deleted')){ $table->dropColumn('deleted'); }
        });
    }
};
