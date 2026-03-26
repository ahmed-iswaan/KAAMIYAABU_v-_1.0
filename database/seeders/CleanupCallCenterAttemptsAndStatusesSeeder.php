<?php

namespace Database\Seeders;

use App\Models\Directory;
use App\Models\ElectionDirectoryCallStatus;
use App\Models\ElectionDirectoryCallSubStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanupCallCenterAttemptsAndStatusesSeeder extends Seeder
{
    /**
     * Cleanup seeder:
     * For directories with NO phone numbers, if they have attempts then:
     * - delete ALL attempts
     * - delete ALL call status rows
     *
     * Outputs counts of what was found and what was removed.
     */
    public function run(): void
    {
        $stats = DB::transaction(function () {
            $directoryIdsNoPhones = Directory::query()
                ->where(function ($q) {
                    // phones is stored as JSON; empty phone list is JSON array [] (JSON_LENGTH = 0)
                    // Also keep null/empty string fallbacks for legacy data.
                    $q->whereNull('phones')
                        ->orWhere('phones', '=', '')
                        ->orWhereRaw('JSON_VALID(phones)=1 AND JSON_LENGTH(phones)=0');
                })
                ->pluck('id')
                ->map(fn ($id) => (string) $id)
                ->all();

            if (!count($directoryIdsNoPhones)) {
                return [
                    'directories_no_phones' => 0,
                    'directories_matched' => 0,
                    'attempt_rows_found' => 0,
                    'status_rows_found' => 0,
                    'attempt_rows_deleted' => 0,
                    'status_rows_deleted' => 0,
                ];
            }

            // Directories (no phones) that have attempts
            $attemptDirIds = ElectionDirectoryCallSubStatus::query()
                ->whereIn('directory_id', $directoryIdsNoPhones)
                ->distinct()
                ->pluck('directory_id')
                ->map(fn ($id) => (string) $id)
                ->all();

            $directoriesMatched = count($attemptDirIds);
            if (!$directoriesMatched) {
                return [
                    'directories_no_phones' => count($directoryIdsNoPhones),
                    'directories_matched' => 0,
                    'attempt_rows_found' => 0,
                    'status_rows_found' => 0,
                    'attempt_rows_deleted' => 0,
                    'status_rows_deleted' => 0,
                ];
            }

            $attemptRowsFound = ElectionDirectoryCallSubStatus::query()
                ->whereIn('directory_id', $attemptDirIds)
                ->count();

            $statusRowsFound = ElectionDirectoryCallStatus::query()
                ->whereIn('directory_id', $attemptDirIds)
                ->count();

            // Delete and capture deleted counts
            $attemptRowsDeleted = ElectionDirectoryCallSubStatus::query()
                ->whereIn('directory_id', $attemptDirIds)
                ->delete();

            $statusRowsDeleted = ElectionDirectoryCallStatus::query()
                ->whereIn('directory_id', $attemptDirIds)
                ->delete();

            return [
                'directories_no_phones' => count($directoryIdsNoPhones),
                'directories_matched' => $directoriesMatched,
                'attempt_rows_found' => $attemptRowsFound,
                'status_rows_found' => $statusRowsFound,
                'attempt_rows_deleted' => (int) $attemptRowsDeleted,
                'status_rows_deleted' => (int) $statusRowsDeleted,
            ];
        });

        $this->command?->info('CleanupCallCenterAttemptsAndStatusesSeeder finished.');
        $this->command?->line('Directories with no phones: ' . ($stats['directories_no_phones'] ?? 0));
        $this->command?->line('Directories matched (no phones + has attempts): ' . ($stats['directories_matched'] ?? 0));
        $this->command?->line('Attempt rows found: ' . ($stats['attempt_rows_found'] ?? 0) . ' | deleted: ' . ($stats['attempt_rows_deleted'] ?? 0));
        $this->command?->line('Status rows found: ' . ($stats['status_rows_found'] ?? 0) . ' | deleted: ' . ($stats['status_rows_deleted'] ?? 0));
    }
}
