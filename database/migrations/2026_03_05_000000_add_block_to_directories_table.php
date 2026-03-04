<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->string('block')->nullable()->after('serial');
            $table->index('block');
        });
    }

    public function down(): void
    {
        Schema::table('directories', function (Blueprint $table) {
            $table->dropIndex(['block']);
            $table->dropColumn('block');
        });
    }
};
