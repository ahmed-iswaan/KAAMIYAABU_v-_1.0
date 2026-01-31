<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('election_directory_call_sub_statuses', function (Blueprint $table) {
            if (!Schema::hasColumn('election_directory_call_sub_statuses', 'phone_number')) {
                $table->string('phone_number', 30)->nullable()->after('directory_id');
                // Explicit short index name to avoid MySQL 64-char identifier limit
                $table->index(['election_id', 'directory_id', 'phone_number'], 'edcss_eid_did_phone_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('election_directory_call_sub_statuses', function (Blueprint $table) {
            if (Schema::hasColumn('election_directory_call_sub_statuses', 'phone_number')) {
                $table->dropIndex('edcss_eid_did_phone_idx');
                $table->dropColumn('phone_number');
            }
        });
    }
};
