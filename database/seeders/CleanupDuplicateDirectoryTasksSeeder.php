<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Task;
use App\Models\EventLog;

class CleanupDuplicateDirectoryTasksSeeder extends Seeder
{
    protected bool $dryRun;

    public function __construct()
    {
        // Set TASK_DUPLICATE_CLEANUP_DRY_RUN=false in .env to actually mark deleted
        $this->dryRun = filter_var(env('TASK_DUPLICATE_CLEANUP_DRY_RUN', false), FILTER_VALIDATE_BOOL);
    }

    public function run(): void
    {
        $duplicateDirectoryIds = Task::withDeleted()
            ->where('deleted', false)
            ->whereNotNull('directory_id')
            ->select('directory_id')
            ->groupBy('directory_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('directory_id');

        $totalDirectories = $duplicateDirectoryIds->count();
        $totalMarkedDeleted = 0;
        $totalKept = 0;

        $summary = [];

        Log::info('Starting duplicate task cleanup', [
            'directories_with_duplicates' => $totalDirectories,
            'dry_run' => $this->dryRun,
        ]);

        foreach ($duplicateDirectoryIds->chunk(200) as $chunk) {
            foreach ($chunk as $directoryId) {
                $tasks = Task::withDeleted()
                    ->where('deleted', false)
                    ->where('directory_id', $directoryId)
                    ->get();

                if ($tasks->count() <= 1) {
                    continue; // should not happen due to havingRaw
                }

                $completed = $tasks->filter(fn($t) => $t->status === 'completed');
                $followUps = $tasks->filter(fn($t) => $t->status === 'follow_up');
                $pending = $tasks->filter(fn($t) => $t->status === 'pending');

                if ($completed->isNotEmpty()) {
                    $keep = $completed->sortByDesc(fn($t) => $t->completed_at ?? $t->updated_at ?? $t->created_at)->first();
                    $rule = 'latest_completed';
                } elseif ($followUps->isNotEmpty()) {
                    $keep = $followUps->sortByDesc(fn($t) => $t->follow_up_date ?? $t->updated_at ?? $t->created_at)->first();
                    $rule = 'latest_follow_up';
                } elseif ($pending->isNotEmpty()) {
                    $keep = $pending->sortBy(fn($t) => $t->created_at)->first();
                    $rule = 'earliest_pending';
                } else {
                    // Unexpected statuses — keep first
                    $keep = $tasks->first();
                    $rule = 'fallback_first';
                }

                $toDelete = $tasks->reject(fn($t) => $t->id === $keep->id);
                $deleteIds = $toDelete->pluck('id');

                if ($deleteIds->isNotEmpty()) {
                    if (! $this->dryRun) {
                        DB::transaction(function () use ($deleteIds, $directoryId, $keep, $rule) {
                            Task::whereIn('id', $deleteIds)->update(['deleted' => true, 'deleted_at' => now(), 'deleted_by' => 1]);
                            EventLog::create([
                                'event_type' => 'task_directory_cleanup',
                                'event_tab' => 'tasks',
                                'event_entry_id' => $keep->id,
                                'task_id' => $keep->id,
                                'description' => 'Directory task duplicates cleaned',
                                'event_data' => [
                                    'directory_id' => $directoryId,
                                    'kept_task_id' => $keep->id,
                                    'removed_task_ids' => $deleteIds->values(),
                                    'rule' => $rule,
                                    'deleted_by' => 1,
                                ],
                                'user_id' => 1,
                                'ip_address' => request()->ip(),
                            ]);
                        });
                    }
                    $totalMarkedDeleted += $deleteIds->count();
                }

                $summary[] = [
                    'directory_id' => $directoryId,
                    'kept_task_id' => $keep->id,
                    'rule' => $rule,
                    'removed_count' => $deleteIds->count(),
                ];

                $totalKept++;
            }
        }

        Log::info('Duplicate task cleanup finished', [
            'directories_processed' => $totalDirectories,
            'tasks_kept' => $totalKept,
            'tasks_marked_deleted' => $this->dryRun ? 0 : $totalMarkedDeleted,
            'dry_run' => $this->dryRun,
        ]);

        if ($this->dryRun) {
            $this->command?->warn('Dry run: no tasks marked deleted. Set TASK_DUPLICATE_CLEANUP_DRY_RUN=false and rerun to apply changes.');
        } else {
            $this->command?->info("Cleanup applied: {$totalMarkedDeleted} duplicate tasks marked deleted across {$totalDirectories} directories.");
        }

        // Optional concise console table output (if available)
        if(method_exists($this->command,'table')){
            $this->command->table(
                ['Directory','Kept Task','Rule','Removed'],
                collect($summary)->map(fn($r)=>[$r['directory_id'],$r['kept_task_id'],$r['rule'],$r['removed_count']])->toArray()
            );
        }
    }
}
