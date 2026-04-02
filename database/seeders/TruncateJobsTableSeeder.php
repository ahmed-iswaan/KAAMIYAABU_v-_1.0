<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TruncateJobsTableSeeder extends Seeder
{
    public function run(): void
    {
        // Works for MySQL and SQLite. On Postgres, TRUNCATE is also supported.
        // Use statement to avoid needing model.
        DB::table('jobs')->delete();

        // Reset AUTO_INCREMENT where supported.
        try {
            DB::statement('ALTER TABLE jobs AUTO_INCREMENT = 1');
        } catch (\Throwable $e) {
            // ignore (not supported on some DBs)
        }
    }
}
