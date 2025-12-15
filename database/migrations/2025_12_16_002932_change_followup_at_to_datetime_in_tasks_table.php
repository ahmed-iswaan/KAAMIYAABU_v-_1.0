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
        Schema::table('tasks', function (Blueprint $table) {
            // Some DBs don't support modifying column types easily; do drop+add
            if (Schema::hasColumn('tasks','followup_at')) {
                $table->dropColumn('followup_at');
            }
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->dateTime('followup_at')->nullable()->after('due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('followup_at');
        });
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('followup_at')->nullable()->after('due_at');
        });
    }
};
