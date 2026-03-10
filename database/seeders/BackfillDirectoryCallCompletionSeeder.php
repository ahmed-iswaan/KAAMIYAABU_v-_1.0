<?php

namespace Database\Seeders;

use App\Models\CallCenterForm;
use App\Models\Election;
use App\Models\ElectionDirectoryCallStatus;
use App\Models\ElectionDirectoryCallSubStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BackfillDirectoryCallCompletionSeeder extends Seeder
{
    /**
     * Mark directories as completed for the current active election when:
     *  - Call Center form has Q1 and Q3 answered (Q2 ignored)
     *  - OR there is at least one call attempt with a sub-status that maps to a "terminal" call outcome
     */
    public function run(): void
    {
        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        if (!$activeElectionId) {
            $this->command?->warn('BackfillDirectoryCallCompletionSeeder: No active election found.');
            return;
        }

        // Attempt labels that should complete the directory for the election
        // (these are stored in election_directory_call_sub_statuses.sub_status_id and reference sub_statuses.name)
        $terminalAttemptNames = [
            'phone hung up',
            'wrong number',
            'would decide after speaking with mayor',
            'deceased',
        ];
        $terminalAttemptNamesLower = array_map('strtolower', $terminalAttemptNames);

        DB::transaction(function () use ($activeElectionId, $terminalAttemptNamesLower) {
            // 1) Complete via form answers (Q1 + Q3)
            $formDirectoryIds = CallCenterForm::query()
                ->where('election_id', (string) $activeElectionId)
                ->whereNotNull('q1_performance')
                ->where('q1_performance', '!=', '')
                ->whereNotNull('q3_support')
                ->where('q3_support', '!=', '')
                ->pluck('directory_id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values();

            // 2) Complete via attempts sub-status names
            $attemptDirectoryIds = ElectionDirectoryCallSubStatus::query()
                ->join('sub_statuses', 'sub_statuses.id', '=', 'election_directory_call_sub_statuses.sub_status_id')
                ->where('election_directory_call_sub_statuses.election_id', (string) $activeElectionId)
                ->whereIn(DB::raw('LOWER(sub_statuses.name)'), $terminalAttemptNamesLower)
                ->pluck('election_directory_call_sub_statuses.directory_id')
                ->map(fn ($id) => (string) $id)
                ->unique()
                ->values();

            $toComplete = $formDirectoryIds
                ->merge($attemptDirectoryIds)
                ->unique()
                ->values();

            if ($toComplete->isEmpty()) {
                $this->command?->info('BackfillDirectoryCallCompletionSeeder: Nothing to backfill.');
                return;
            }

            $created = 0;
            $updated = 0;

            foreach ($toComplete as $directoryId) {
                $row = ElectionDirectoryCallStatus::query()->firstOrNew([
                    'election_id' => (string) $activeElectionId,
                    'directory_id' => (string) $directoryId,
                ]);

                $current = $row->exists ? ($row->status ?: ElectionDirectoryCallStatus::STATUS_NOT_STARTED) : null;

                if ($current === ElectionDirectoryCallStatus::STATUS_COMPLETED) {
                    continue;
                }

                if (!$row->exists) {
                    $created++;
                } else {
                    $updated++;
                }

                $row->status = ElectionDirectoryCallStatus::STATUS_COMPLETED;
                $row->completed_at = now();

                // Seeder has no user context
                $row->updated_by = null;

                $row->save();
            }

            $this->command?->info("BackfillDirectoryCallCompletionSeeder: completed directories backfilled. created={$created}, updated={$updated}");
        });
    }
}
