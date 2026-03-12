<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // These indexes are safe with existing data (non-unique) and improve Call Center queries.

        Schema::table('election_directory_call_statuses', function (Blueprint $table) {
            // Helps filtering completed/pending by election and correlating by directory
            $table->index(['election_id', 'status', 'directory_id'], 'edcs_election_status_directory_idx');

            // Helps "completed by me" counts
            $table->index(['election_id', 'status', 'updated_by'], 'edcs_election_status_updatedby_idx');
        });

        Schema::table('election_directory_call_sub_statuses', function (Blueprint $table) {
            // Helps lookup latest attempt rows per directory for an election
            $table->index(['election_id', 'directory_id', 'attempt'], 'edcss_election_directory_attempt_idx');
        });

        Schema::table('directories', function (Blueprint $table) {
            // Helps the main directories list query (status + allowed sub consites)
            $table->index(['status', 'sub_consite_id'], 'directories_status_subconsite_idx');
        });
    }

    public function down(): void
    {
        Schema::table('election_directory_call_statuses', function (Blueprint $table) {
            $table->dropIndex('edcs_election_status_directory_idx');
            $table->dropIndex('edcs_election_status_updatedby_idx');
        });

        Schema::table('election_directory_call_sub_statuses', function (Blueprint $table) {
            $table->dropIndex('edcss_election_directory_attempt_idx');
        });

        Schema::table('directories', function (Blueprint $table) {
            $table->dropIndex('directories_status_subconsite_idx');
        });
    }
};
