<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use App\Models\Directory;
use App\Models\Task;
use App\Models\EventLog;

class MarkTasksDeletedFromNewUpdateSeeder extends Seeder
{
    /**
     * Mark tasks as deleted if their directory is not listed in seeders/data/electionmdplist.json.
     */
    public function run(): void
    {
        $file = database_path('seeders/data/electionmdplist.json');
        if (!File::exists($file)) {
            $this->command->error("JSON file missing: {$file}");
            return;
        }

        $rows = json_decode(File::get($file), true);
        if (!is_array($rows)) {
            $this->command->error('electionmdplist.json not valid JSON array.');
            return;
        }

        // Collect all id card numbers present in JSON
        $validIdCards = collect($rows)
            ->map(function ($r) {
                return trim($r['Id #'] ?? $r['id_card'] ?? '');
            })
            ->filter()
            ->unique()
            ->values();

        if ($validIdCards->isEmpty()) {
            $this->command->warn('No valid Id # values found in electionmdplist.json; no tasks will be marked deleted.');
            return;
        }

        // Find directories NOT in the JSON list
        $directoriesToMark = Directory::whereNotIn('id_card_number', $validIdCards)->pluck('id');
        if ($directoriesToMark->isEmpty()) {
            $this->command->info('No directories found outside electionmdplist.json; no tasks changed.');
            return;
        }

        $this->command->info('Found ' . $directoriesToMark->count() . ' directories not in electionmdplist.json. Marking related tasks as deleted...');

        $totalTasks = 0;
        $updatedTasks = 0;

        Task::whereIn('directory_id', $directoriesToMark)
            ->chunkById(500, function ($tasks) use (&$totalTasks, &$updatedTasks) {
                foreach ($tasks as $task) {
                    $totalTasks++;

                    if ($task->deleted_at) {
                        continue; // already soft deleted
                    }

                    $task->delete();
                    $updatedTasks++;

                    // Log event
                    EventLog::create([
                        'user_id' => auth()->id() ?? null,
                        'event_type' => 'task_deleted',
                        'event_tab' => 'task',
                        'event_entry_id' => $task->id,
                        'description' => 'Task soft-deleted via MarkTasksDeletedFromNewUpdateSeeder because directory is not in electionmdplist.json 03/01/2026',
                        'event_data' => [
                            'directory_id' => $task->directory_id,
                        ],
                        'ip_address' => request()->ip() ?? null,
                    ]);
                }
            });

        $this->command->info("✅ Task delete marking complete. Total tasks scanned: {$totalTasks} | Newly marked deleted: {$updatedTasks}");
    }
}
