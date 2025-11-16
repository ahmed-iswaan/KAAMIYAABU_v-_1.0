<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tasks', 'sub_status_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->uuid('sub_status_id')->nullable()->after('status');
                $table->foreign('sub_status_id')->references('id')->on('sub_statuses')->nullOnDelete();
                $table->index('sub_status_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks', 'sub_status_id')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropForeign(['sub_status_id']);
                $table->dropIndex(['sub_status_id']);
                $table->dropColumn('sub_status_id');
            });
        }
    }
};
