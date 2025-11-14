<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('tasks','follow_up_date')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dateTime('follow_up_date')->nullable()->after('due_at');
                $table->index('follow_up_date');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('tasks','follow_up_date')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dropIndex(['follow_up_date']);
                $table->dropColumn('follow_up_date');
            });
        }
    }
};
