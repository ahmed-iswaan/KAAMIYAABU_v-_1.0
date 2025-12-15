<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use App\Models\Directory;
use App\Models\EventLog;

class DeactivateNonListedDirectoriesSeeder extends Seeder
{
    /**
     * Keep only directories present in updatevoterslist.json as Active,
     * and set status of all other directories to Inactive.
     */
    public function run(): void
    {
        $file = database_path('seeders/data/updatevoterslist.json');
        if (!File::exists($file)) {
            $this->command->error("JSON file missing: {$file}");
            return;
        }

        $rows = json_decode(File::get($file), true);
        if (!is_array($rows)) {
            $this->command->error('updatevoterslist.json is not a valid JSON array.');
            return;
        }

        $idCards = collect($rows)
            ->map(fn($r) => trim($r['Id #'] ?? ''))
            ->filter()
            ->unique()
            ->values();

        $expectedCount = 5608; // based on current dataset size
        if ($idCards->count() !== $expectedCount) {
            $this->command->warn("Warning: collected {$idCards->count()} IDs, expected {$expectedCount}.");
        }

        $totalBefore = Directory::count();
        $keepCountDb = Directory::whereIn('id_card_number', $idCards)->count();
        $toProcessCount = Directory::whereNotIn('id_card_number', $idCards)->count();

        $this->command->info("Directories total: {$totalBefore}");
        $this->command->info("Keep (present in list): {$keepCountDb}");
        $this->command->info("Not in list (to ensure Inactive): {$toProcessCount}");

        $changed = 0; $processed = 0; $alreadyInactive = 0;
        Directory::whereNotIn('id_card_number', $idCards)
            ->orderBy('id')
            ->chunk(500, function($chunk) use (&$changed, &$processed, &$alreadyInactive) {
                foreach ($chunk as $dir) {
                    $processed++;
                    $old = $dir->status;
                    if ($old !== 'Inactive') {
                        $dir->status = 'Inactive';
                        $dir->save();
                        $changed++;

                        EventLog::create([
                            'user_id' => auth()->id() ?? null,
                            'event_type' => 'directory_status_changed',
                            'event_tab' => 'directory',
                            'event_entry_id' => $dir->id,
                            'description' => 'Directory status set to Inactive (not in voters update list)',
                            'event_data' => [
                                'field' => 'status',
                                'from' => $old,
                                'to' => 'Inactive',
                                'id_card_number' => $dir->id_card_number,
                                'name' => $dir->name,
                            ],
                            'ip_address' => null,
                        ]);
                    } else {
                        $alreadyInactive++;
                    }

                    if ($processed % 1000 === 0) {
                        $this->command->info("Processed {$processed} ...");
                    }
                }
            });

        $inactiveAfter = Directory::where('status', 'Inactive')->count();
        $this->command->info("✅ Deactivation done. Changed to Inactive: {$changed}. Already Inactive: {$alreadyInactive}. Inactive now: {$inactiveAfter}");
    }
}
