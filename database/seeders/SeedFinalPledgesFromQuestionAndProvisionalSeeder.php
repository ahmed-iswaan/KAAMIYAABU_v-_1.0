<?php

namespace Database\Seeders;

use App\Models\Directory;
use App\Models\Election;
use App\Models\VoterPledge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeedFinalPledgesFromQuestionAndProvisionalSeeder extends Seeder
{
    /**
     * Rules (from the provided matrix):
     * - Final = YES if (ANY provisional pledge (any user) == yes) OR (Call Centre == Yes)
     * - Final = NO if there is NO yes anywhere but there is some data (provisional no/neutral OR call centre no/undecided/not reached)
     * - Final = PENDING if there is no data from both sources
     */
    public function run(): void
    {
        // Use ACTIVE election
        $electionId = Election::query()->where('is_active', 1)->value('id');
        if (!$electionId) {
            $this->command?->warn('No active election found. Seeder aborted.');
            return;
        }

        // Question 3 mapping (from CallCenterForm.q3_support)
        $qMap = [
            'aanekey' => 'yes',
            'noonekay' => 'no',
            'neyngey' => 'undecided',
            'vote_laan_nudhaanan' => 'not_voting',
        ];

        // Get all active directory ids
        $activeDirectoryIds = Directory::query()
            ->where('status', 'Active')
            ->pluck('id');

        $now = now();
        $createdBy = null; // system

        $updated = 0;
        $inserted = 0;
        $skipped = 0;

        foreach ($activeDirectoryIds as $directoryId) {
            // Provisional aggregation across all users for this directory/election
            $provAnyYes = DB::table('voter_provisional_user_pledges')
                ->where('election_id', $electionId)
                ->where('directory_id', $directoryId)
                ->where('status', 'yes')
                ->exists();

            $provAnyNo = DB::table('voter_provisional_user_pledges')
                ->where('election_id', $electionId)
                ->where('directory_id', $directoryId)
                ->where('status', 'no')
                ->exists();

            $provAnyUndecided = DB::table('voter_provisional_user_pledges')
                ->where('election_id', $electionId)
                ->where('directory_id', $directoryId)
                ->where('status', 'neutral')
                ->exists();

            // Call center answer from CallCenterForm (latest row)
            $q3Raw = DB::table('call_center_forms')
                ->where('election_id', $electionId)
                ->where('directory_id', $directoryId)
                ->whereNotNull('q3_support')
                ->orderByDesc('created_at')
                ->value('q3_support');

            $qNormalized = $q3Raw ? ($qMap[$q3Raw] ?? null) : null;

            // Decide final according to the matrix
            $final = null;

            $hasAnyProvData = ($provAnyYes || $provAnyNo || $provAnyUndecided);
            $hasAnyCallCentreData = !is_null($qNormalized);

            // YES if any YES anywhere
            if ($provAnyYes || $qNormalized === 'yes') {
                $final = 'yes';
            } elseif ($hasAnyProvData || $hasAnyCallCentreData) {
                // If there is data but no yes, final is NO (covers No/Undecided/Not reached combos)
                $final = 'no';
            } else {
                // No data at all
                $final = null; // pending
            }

            if (!$final) {
                $skipped++;
                continue;
            }

            // Upsert final pledge row
            $existing = VoterPledge::query()
                ->where('directory_id', $directoryId)
                ->where('election_id', $electionId)
                ->where('type', VoterPledge::TYPE_FINAL)
                ->first();

            if ($existing) {
                $existing->status = $final;
                $existing->note = 'Seeded from provisional pledges + CallCenterForm.q3_support';
                $existing->created_by = $existing->created_by ?? $createdBy;
                $existing->updated_at = $now;
                $existing->save();
                $updated++;
            } else {
                VoterPledge::query()->create([
                    'directory_id' => $directoryId,
                    'election_id' => $electionId,
                    'type' => VoterPledge::TYPE_FINAL,
                    'status' => $final,
                    'note' => 'Seeded from provisional pledges + CallCenterForm.q3_support',
                    'created_by' => $createdBy,
                ]);
                $inserted++;
            }
        }

        $this->command?->info("Final pledge seeding complete. inserted={$inserted}, updated={$updated}, skipped(pending)={$skipped}");
    }
}
