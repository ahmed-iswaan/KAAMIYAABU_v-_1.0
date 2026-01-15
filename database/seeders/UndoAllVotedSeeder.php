<?php

namespace Database\Seeders;

use App\Models\Election;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UndoAllVotedSeeder extends Seeder
{
    /**
     * Deletes voted marks (voted_representatives) for an election.
     *
     * By default it targets the latest election (by start_date).
     * You can optionally pass an election id using: php artisan db:seed --class=UndoAllVotedSeeder -- --election=ID
     */
    public function run(): void
    {
        $electionId = null;

        // Try to read CLI option: --election=
        try {
            $electionId = $this->command?->option('election');
        } catch (\Throwable $e) {
            // ignore when seeder run without command context
        }

        if (!$electionId) {
            $electionId = Election::orderBy('start_date', 'desc')->value('id');
        }

        if (!$electionId) {
            $this->command?->warn('No election found. Seeder aborted.');
            return;
        }

        $deleted = DB::table('voted_representatives')
            ->where('election_id', $electionId)
            ->delete();

        $this->command?->info("UndoAllVotedSeeder: deleted {$deleted} voted marks for election_id={$electionId}");
    }
}
