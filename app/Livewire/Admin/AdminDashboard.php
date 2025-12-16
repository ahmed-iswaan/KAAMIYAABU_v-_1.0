<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Directory;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminDashboard extends Component
{
    use AuthorizesRequests;

    public $activeDirectories = 0;
    public $tasksPending = 0;
    public $tasksFollowUp = 0;
    public $tasksCompleted = 0;
    public $directoriesWithNoTasks = 0;

    public $piePendingDirs = 0;
    public $pieFollowUpDirs = 0;
    public $pieCompletedDirs = 0;

    public $subConsiteLabels = [];
    public $subConsitePending = [];
    public $subConsiteFollowUp = [];
    public $subConsiteCompleted = [];
    public $subConsiteNoTask = [];

    public $dirSubConsiteLabels = [];
    public $dirMaleCounts = [];
    public $dirFemaleCounts = [];
    public $dirOtherCounts = [];

    public function mount(): void
    {
        // Do not block with authorize to avoid zeros; authorize in route/middleware
        $this->computeStats();
    }

    private function computeStats(): void
    {
        // Active directories
        $this->activeDirectories = (int) Directory::where('status','Active')->count();

        // Task aggregates
        $taskAgg = DB::table('tasks')
            ->where('deleted', false)
            ->selectRaw("SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status='follow_up' THEN 1 ELSE 0 END) as follow_up")
            ->selectRaw("SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed")
            ->first();
        $this->tasksPending = (int)($taskAgg->pending ?? 0);
        $this->tasksFollowUp = (int)($taskAgg->follow_up ?? 0);
        $this->tasksCompleted = (int)($taskAgg->completed ?? 0);

        // Directories with no tasks
        $this->directoriesWithNoTasks = (int) Directory::where('status','Active')
            ->whereNotExists(function($q){
                $q->selectRaw(1)
                  ->from('tasks')
                  ->whereColumn('tasks.directory_id','directories.id')
                  ->where('tasks.deleted',false);
            })->count();

        // Classify Active directories by latest task status (mutually exclusive): Pending / Follow-up / Completed / No Task
        $latestTaskPerDir = DB::table('directories as d')
            ->leftJoin(DB::raw('(SELECT directory_id, MAX(id) as last_task_id FROM tasks WHERE deleted = 0 GROUP BY directory_id) lt'), 'lt.directory_id', '=', 'd.id')
            ->leftJoin('tasks as t', 't.id', '=', 'lt.last_task_id')
            ->where('d.status', 'Active')
            ->selectRaw(
                "SUM(CASE WHEN lt.last_task_id IS NULL THEN 1 ELSE 0 END) as no_task, ".
                "SUM(CASE WHEN t.status = 'pending' THEN 1 ELSE 0 END) as pending_dirs, ".
                "SUM(CASE WHEN t.status = 'follow_up' THEN 1 ELSE 0 END) as follow_up_dirs, ".
                "SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_dirs"
            )
            ->first();
        $this->directoriesWithNoTasks = (int)($latestTaskPerDir->no_task ?? $this->directoriesWithNoTasks);
        $this->piePendingDirs = (int)($latestTaskPerDir->pending_dirs ?? 0);
        $this->pieFollowUpDirs = (int)($latestTaskPerDir->follow_up_dirs ?? 0);
        $this->pieCompletedDirs = (int)($latestTaskPerDir->completed_dirs ?? 0);

        // SubConsite status including No Task (directories without any tasks)
        $rows = DB::table('sub_consites')
            ->leftJoin('directories', function($join){
                $join->on('directories.sub_consite_id','=','sub_consites.id');
            })
            ->leftJoin('tasks', function($join){
                $join->on('tasks.directory_id','=','directories.id')
                     ->where('tasks.deleted', false);
            })
            ->select('sub_consites.id','sub_consites.code')
            ->selectRaw("COALESCE(SUM(CASE WHEN tasks.status='pending' THEN 1 ELSE 0 END),0) as pending")
            ->selectRaw("COALESCE(SUM(CASE WHEN tasks.status='follow_up' THEN 1 ELSE 0 END),0) as follow_up")
            ->selectRaw("COALESCE(SUM(CASE WHEN tasks.status='completed' THEN 1 ELSE 0 END),0) as completed")
            ->selectRaw("COUNT(DISTINCT CASE WHEN directories.status='Active' THEN directories.id END) as active_dirs")
            ->selectRaw("COUNT(DISTINCT tasks.directory_id) as task_dirs")
            ->groupBy('sub_consites.id','sub_consites.code')
            ->orderBy('sub_consites.code')
            ->get();
        $this->subConsiteLabels = $rows->pluck('code')->toArray();
        $this->subConsitePending = $rows->pluck('pending')->map(fn($v)=> (int) $v)->toArray();
        $this->subConsiteFollowUp = $rows->pluck('follow_up')->map(fn($v)=> (int) $v)->toArray();
        $this->subConsiteCompleted = $rows->pluck('completed')->map(fn($v)=> (int) $v)->toArray();
        $this->subConsiteNoTask = $rows->map(fn($r)=> max(0, (int)($r->active_dirs ?? 0) - (int)($r->task_dirs ?? 0)))->toArray();

        // Active directories by gender grouped by sub_consite (LEFT JOIN to include subs with zero)
        $dirRows = DB::table('sub_consites')
            ->leftJoin('directories','directories.sub_consite_id','=','sub_consites.id')
            ->select('sub_consites.code as code')
            ->selectRaw("SUM(CASE WHEN directories.status='Active' AND directories.gender='male' THEN 1 ELSE 0 END) as male")
            ->selectRaw("SUM(CASE WHEN directories.status='Active' AND directories.gender='female' THEN 1 ELSE 0 END) as female")
            ->selectRaw("SUM(CASE WHEN directories.status='Active' AND (directories.gender IS NULL OR directories.gender NOT IN ('male','female')) THEN 1 ELSE 0 END) as other")
            ->groupBy('sub_consites.code')
            ->orderBy('sub_consites.code')
            ->get();
        $this->dirSubConsiteLabels = $dirRows->pluck('code')->toArray();
        $this->dirMaleCounts = $dirRows->pluck('male')->map(fn($v)=> (int) ($v ?? 0))->toArray();
        $this->dirFemaleCounts = $dirRows->pluck('female')->map(fn($v)=> (int) ($v ?? 0))->toArray();
        $this->dirOtherCounts = $dirRows->pluck('other')->map(fn($v)=> (int) ($v ?? 0))->toArray();
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard',[
            'activeDirectories' => $this->activeDirectories,
            'tasksPending' => $this->tasksPending,
            'tasksFollowUp' => $this->tasksFollowUp,
            'tasksCompleted' => $this->tasksCompleted,
            'directoriesWithNoTasks' => $this->directoriesWithNoTasks,
            // Pie segments
            'piePendingDirs' => $this->piePendingDirs,
            'pieFollowUpDirs' => $this->pieFollowUpDirs,
            'pieCompletedDirs' => $this->pieCompletedDirs,
            'subConsiteLabels' => $this->subConsiteLabels,
            'subConsitePending' => $this->subConsitePending,
            'subConsiteFollowUp' => $this->subConsiteFollowUp,
            'subConsiteCompleted' => $this->subConsiteCompleted,
            'subConsiteNoTask' => $this->subConsiteNoTask,
            'dirSubConsiteLabels' => $this->dirSubConsiteLabels,
            'dirMaleCounts' => $this->dirMaleCounts,
            'dirFemaleCounts' => $this->dirFemaleCounts,
            'dirOtherCounts' => $this->dirOtherCounts,
        ])->layout('layouts.master');
    }
}
