<?php

namespace Database\Seeders;

use App\Models\Election;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UndoAllVotedSeeder extends Seeder
{
    /**
     * Deletes voted marks (voted_representatives) for election(s).
     *
     * - If --election=ID is provided, it targets only that election.
     * - Otherwise it targets all ACTIVE elections.
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

        $electionIds = [];

        if ($electionId) {
            $electionIds = [(string) $electionId];
        } else {
            $electionIds = Election::query()
                ->where('status', 'Active')
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();
        }

        if (empty($electionIds)) {
            $this->command?->warn('No active election found. Seeder aborted.');
            return;
        }

        $deleted = DB::table('voted_representatives')
            ->whereIn('election_id', $electionIds)
            ->delete();

        $this->command?->info('UndoAllVotedSeeder: deleted '.$deleted.' voted marks for election_id in ['.implode(',', $electionIds).']');
    }
}
