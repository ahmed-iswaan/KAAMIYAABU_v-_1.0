<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // MySQL/MariaDB store ENUM allowed values in the column definition.
        // We need to alter it to include 'not_voting' while preserving existing values.
        DB::statement("ALTER TABLE `voter_provisional_user_pledges` MODIFY `status` ENUM('yes','no','neutral','not_voting') NULL");
    }

    public function down(): void
    {
        // Revert back (note: existing 'not_voting' rows will become invalid if any exist)
        DB::statement("ALTER TABLE `voter_provisional_user_pledges` MODIFY `status` ENUM('yes','no','neutral') NULL");
    }
};
