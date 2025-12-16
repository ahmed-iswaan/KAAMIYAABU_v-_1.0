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
    public $subConsiteLabels = [];
    public $subConsitePending = [];
    public $subConsiteFollowUp = [];
    public $subConsiteCompleted = [];
    public $subConsiteNoTasks = [];

    public function mount(): void
    {
        $this->authorize('admin-dashboard-render');
        $this->computeStats();
    }

    private function computeStats(): void
    {
        $this->activeDirectories = Directory::where('status','Active')->count();

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
        $this->directoriesWithNoTasks = Directory::where('status','Active')
            ->whereNotExists(function($q){
                $q->selectRaw(1)->from('tasks')->whereColumn('tasks.directory_id','directories.id')->where('tasks.deleted',false);
            })->count();

        // Status by sub_consites (based on directories' sub_consite_id)
        $rows = DB::table('tasks')
            ->join('directories','tasks.directory_id','=','directories.id')
            ->join('sub_consites','directories.sub_consite_id','=','sub_consites.id')
            ->where('tasks.deleted', false)
            ->select('sub_consites.code as code')
            ->selectRaw("SUM(CASE WHEN tasks.status='pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN tasks.status='follow_up' THEN 1 ELSE 0 END) as follow_up")
            ->selectRaw("SUM(CASE WHEN tasks.status='completed' THEN 1 ELSE 0 END) as completed")
            ->groupBy('sub_consites.code')
            ->orderBy('sub_consites.code')
            ->get();
        $this->subConsiteLabels = $rows->pluck('code')->toArray();
        $this->subConsitePending = $rows->pluck('pending')->map(fn($v)=>(int)$v)->toArray();
        $this->subConsiteFollowUp = $rows->pluck('follow_up')->map(fn($v)=>(int)$v)->toArray();
        $this->subConsiteCompleted = $rows->pluck('completed')->map(fn($v)=>(int)$v)->toArray();

        // Active directories with no tasks per sub_consite
        $noTaskRows = DB::table('sub_consites')
            ->leftJoin('directories','directories.sub_consite_id','=','sub_consites.id')
            ->leftJoin('tasks', function($join){
                $join->on('tasks.directory_id','=','directories.id')
                     ->where('tasks.deleted', false);
            })
            ->where('directories.status','Active')
            ->select('sub_consites.code as code')
            ->selectRaw('SUM(CASE WHEN tasks.id IS NULL THEN 1 ELSE 0 END) as no_tasks')
            ->groupBy('sub_consites.code')
            ->orderBy('sub_consites.code')
            ->get();
        // Map to existing label order
        $noTaskMap = collect($noTaskRows)->mapWithKeys(fn($r)=>[$r->code => (int)$r->no_tasks])->toArray();
        $this->subConsiteNoTasks = array_map(fn($code)=> $noTaskMap[$code] ?? 0, $this->subConsiteLabels);
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard',[
            'activeDirectories' => $this->activeDirectories,
            'tasksPending' => $this->tasksPending,
            'tasksFollowUp' => $this->tasksFollowUp,
            'tasksCompleted' => $this->tasksCompleted,
            'directoriesWithNoTasks' => $this->directoriesWithNoTasks,
            'subConsiteLabels' => $this->subConsiteLabels,
            'subConsitePending' => $this->subConsitePending,
            'subConsiteFollowUp' => $this->subConsiteFollowUp,
            'subConsiteCompleted' => $this->subConsiteCompleted,
            'subConsiteNoTasks' => $this->subConsiteNoTasks,
        ])->layout('layouts.master');
    }
}
