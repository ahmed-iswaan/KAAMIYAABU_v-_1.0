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
     * Keep only directories present in finalmdplist.json as Active,
     * and set status of all other directories to Inactive.
     */
    public function run(): void
    {
        $file = database_path('seeders/data/finalmdplist.json');
        if (!File::exists($file)) {
            $this->command->error("JSON file missing: {$file}");
            return;
        }

        $rows = json_decode(File::get($file), true);
        if (!is_array($rows)) {
            $this->command->error('finalmdplist.json is not a valid JSON array.');
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

        // 1) Activate any directories that ARE in the list but currently not Active
        $activated = 0; $activateNoop = 0; $activateProcessed = 0;
        Directory::whereIn('id_card_number', $idCards)
            ->orderBy('id')
            ->chunk(500, function($chunk) use (&$activated, &$activateNoop, &$activateProcessed) {
                foreach ($chunk as $dir) {
                    $activateProcessed++;
                    $old = $dir->status;
                    if ($old !== 'Active') {
                        $dir->status = 'Active';
                        $dir->save();
                        $activated++;

                        EventLog::create([
                            'user_id' => auth()->id() ?? null,
                            'event_type' => 'directory_status_changed',
                            'event_tab' => 'directory',
                            'event_entry_id' => $dir->id,
                            'description' => 'Directory status set to Active (in voters update list 03/01/2026)',
                            'event_data' => [
                                'field' => 'status',
                                'from' => $old,
                                'to' => 'Active',
                                'id_card_number' => $dir->id_card_number,
                                'name' => $dir->name,
                            ],
                            'ip_address' => null,
                        ]);
                    } else {
                        $activateNoop++;
                    }

                    if ($activateProcessed % 2000 === 0) {
                        $this->command->info("Activation processed {$activateProcessed} ...");
                    }
                }
            });

        // 2) Deactivate any directories that are NOT in the list
        $deactivated = 0; $deactivateProcessed = 0; $alreadyInactive = 0;
        Directory::whereNotIn('id_card_number', $idCards)
            ->orderBy('id')
            ->chunk(500, function($chunk) use (&$deactivated, &$deactivateProcessed, &$alreadyInactive) {
                foreach ($chunk as $dir) {
                    $deactivateProcessed++;
                    $old = $dir->status;
                    if ($old !== 'Inactive') {
                        $dir->status = 'Inactive';
                        $dir->save();
                        $deactivated++;

                        EventLog::create([
                            'user_id' => auth()->id() ?? null,
                            'event_type' => 'directory_status_changed',
                            'event_tab' => 'directory',
                            'event_entry_id' => $dir->id,
                            'description' => 'Directory status set to Inactive (not in voters update list 03/01/2026)',
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

                    if ($deactivateProcessed % 2000 === 0) {
                        $this->command->info("Deactivation processed {$deactivateProcessed} ...");
                    }
                }
            });

        $inactiveAfter = Directory::where('status', 'Inactive')->count();
        $activeAfter = Directory::where('status', 'Active')->count();
        $this->command->info(
            "✅ Status sync done. Activated: {$activated} (already active: {$activateNoop}). " .
            "Deactivated: {$deactivated} (already inactive: {$alreadyInactive}). " .
            "Active now: {$activeAfter}. Inactive now: {$inactiveAfter}"
        );
    }
}
