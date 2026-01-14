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
        // Use latest election by start_date (same pattern used elsewhere)
        $electionId = Election::orderBy('start_date', 'desc')->value('id');
        if (!$electionId) {
            $this->command?->warn('No election found. Seeder aborted.');
            return;
        }

        // Question text to read from form submissions
        $questionText = '3. މާލޭގެ މޭޔަރ ކަމަށް އިތުރު ދައުރަކަށް އާދަމް އާޒިމް ކުރިމަތި ލެއްވުމަށް ފެނިވަޑައިގަންވާތޯ؟ ';

        // Map option labels -> normalized value
        $qMap = [
            'ފެނޭ (5)' => 'yes',
            'ނުފެނޭ (4)' => 'no',
            'ނޭނގޭ (4)' => 'undecided',
            // Treat any explicit "not reached" style option as data (non-yes)
            'Not reached' => 'not_reached',
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

            // Question-derived answer (latest submission)
            $qAnswer = DB::table('form_submission_answers as fsa')
                ->join('form_questions as fq', 'fq.id', '=', 'fsa.form_question_id')
                ->join('form_submissions as fs', 'fs.id', '=', 'fsa.form_submission_id')
                ->leftJoin('form_question_options as fqo', function ($join) {
                    $join->on('fqo.form_question_id', '=', 'fq.id')
                        ->whereRaw('(fsa.value_text = fqo.value OR fsa.value_text = fqo.id)');
                })
                ->where('fs.election_id', $electionId)
                ->where('fs.directory_id', $directoryId)
                ->where('fq.question_text', $questionText)
                ->whereNotNull('fsa.value_text')
                ->orderByDesc('fs.created_at')
                ->value('fqo.label');

            $qNormalized = $qAnswer ? ($qMap[$qAnswer] ?? null) : null;

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
                $existing->note = 'Seeded from provisional pledges + form Q3';
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
                    'note' => 'Seeded from provisional pledges + form Q3',
                    'created_by' => $createdBy,
                ]);
                $inserted++;
            }
        }

        $this->command?->info("Final pledge seeding complete. inserted={$inserted}, updated={$updated}, skipped(pending)={$skipped}");
    }
}
