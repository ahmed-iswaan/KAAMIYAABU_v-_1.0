<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Directory;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DashboardOverview extends Component
{
     use AuthorizesRequests;

    public $totalPopulation = 0;
    public $maleCount = 0;
    public $femaleCount = 0;

    // Per-island chart data
    public $islandLabels = [];
    public $islandMaleCounts = [];
    public $islandFemaleCounts = [];
    public $islandTotals = [];

    // Task stats
    public $taskTotal = 0;
    public $taskPending = 0;
    public $taskFollowUp = 0;
    public $taskCompleted = 0;

    // Ranked task stats per user
    public $userTaskStats = [];

    protected $listeners = ['taskChanged' => 'refreshStats'];

    public function mount()
    {
        $this->computeDirectoryStats();
        $this->computeTaskStats();
    }

    // Livewire listener triggered from JS websocket handler
    #[\Livewire\Attributes\On('task-status-updated')]
    public function handleTaskStatusUpdated(): void
    {
        $this->refreshTaskStats();
        // Re-render component
        $this->dispatch('$refresh');
    }

    protected function computeDirectoryStats(): void
    {
        $rows = DB::table('directories')
            ->join('islands','directories.island_id','=','islands.id')
            ->select('islands.name as island_name',
                DB::raw("SUM(CASE WHEN directories.gender='male' THEN 1 ELSE 0 END) as male_count"),
                DB::raw("SUM(CASE WHEN directories.gender='female' THEN 1 ELSE 0 END) as female_count"),
                DB::raw('COUNT(*) as total_count')
            )
            ->where('directories.status','Active')
            ->whereNotNull('directories.island_id')
            ->groupBy('directories.island_id','islands.name')
            ->orderBy('islands.name')
            ->get();
        $this->totalPopulation = $rows->sum('total_count');
        $this->maleCount = $rows->sum('male_count');
        $this->femaleCount = $rows->sum('female_count');
        $this->islandLabels = $rows->pluck('island_name')->toArray();
        $this->islandMaleCounts = $rows->pluck('male_count')->toArray();
        $this->islandFemaleCounts = $rows->pluck('female_count')->toArray();
        $this->islandTotals = $rows->pluck('total_count')->toArray();
    }
    protected function computeTaskStats(): void
    {
        // Logged in user summary (exclude deleted)
        $taskRows = DB::table('tasks')
            ->join('task_user','tasks.id','=','task_user.task_id')
            ->where('task_user.user_id', auth()->id())
            ->where('tasks.deleted', false)
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN tasks.status='pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN tasks.status='follow_up' THEN 1 ELSE 0 END) as follow_up")
            ->selectRaw("SUM(CASE WHEN tasks.status='completed' THEN 1 ELSE 0 END) as completed")
            ->first();
        if($taskRows){
            $this->taskTotal = (int)$taskRows->total;
            $this->taskPending = (int)$taskRows->pending;
            $this->taskFollowUp = (int)$taskRows->follow_up;
            $this->taskCompleted = (int)$taskRows->completed;
        } else {
            $this->taskTotal = $this->taskPending = $this->taskFollowUp = $this->taskCompleted = 0;
        }

        $today = now()->toDateString();
        $userRows = DB::table('users')
            ->join('task_user','users.id','=','task_user.user_id')
            ->join('tasks','task_user.task_id','=','tasks.id')
            ->where('tasks.deleted', false)
            ->select('users.id','users.name',
                DB::raw('COUNT(tasks.id) as total'),
                DB::raw("SUM(CASE WHEN tasks.status='pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN tasks.status='follow_up' THEN 1 ELSE 0 END) as follow_up"),
                DB::raw("SUM(CASE WHEN tasks.status='completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("(SELECT COUNT(*) FROM tasks t2 WHERE t2.completed_by = users.id AND t2.deleted = 0) as completed_by_user"),
                DB::raw("(SELECT COUNT(*) FROM tasks t3 WHERE t3.completed_by = users.id AND t3.deleted = 0 AND DATE(t3.completed_at) = '{$today}') as completed_by_user_today"),
                DB::raw("SUM(CASE WHEN tasks.status='completed' AND DATE(tasks.completed_at) = '{$today}' THEN 1 ELSE 0 END) as completed_today")
            )
            ->groupBy('users.id','users.name')
            ->orderByRaw("(SELECT COUNT(*) FROM tasks t2 WHERE t2.completed_by = users.id AND t2.deleted = 0) DESC")
            ->orderBy('users.name')
            ->get();
        $rank = 1;
        $this->userTaskStats = $userRows->map(function($r) use (&$rank){
            $pct = $r->total ? round(($r->completed / $r->total)*100) : 0;
            return [
                'rank' => $rank++,
                'user_id' => $r->id,
                'name' => $r->name,
                'total' => (int)$r->total,
                'pending' => (int)$r->pending,
                'follow_up' => (int)$r->follow_up,
                'completed' => (int)$r->completed,
                'completed_today' => (int)$r->completed_today,
                'completed_by_user' => (int)$r->completed_by_user,
                'completed_by_user_today' => (int)$r->completed_by_user_today,
                'completed_pct' => $pct,
            ];
        })->toArray();
    }

    public function refreshStats(): void
    {
        $this->computeTaskStats();
    }
    protected function refreshTaskStats(): void
    {
        $taskRows = DB::table('tasks')
            ->join('task_user','tasks.id','=','task_user.task_id')
            ->where('task_user.user_id', auth()->id())
            ->where('tasks.deleted', false)
            ->selectRaw("COUNT(*) as total")
            ->selectRaw("SUM(CASE WHEN tasks.status='pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN tasks.status='follow_up' THEN 1 ELSE 0 END) as follow_up")
            ->selectRaw("SUM(CASE WHEN tasks.status='completed' THEN 1 ELSE 0 END) as completed")
            ->first();
        if($taskRows){
            $this->taskTotal = (int)$taskRows->total;
            $this->taskPending = (int)$taskRows->pending;
            $this->taskFollowUp = (int)$taskRows->follow_up;
            $this->taskCompleted = (int)$taskRows->completed;
        } else {
            $this->taskTotal = $this->taskPending = $this->taskFollowUp = $this->taskCompleted = 0;
        }

        $today = now()->toDateString();
        $rankedRows = DB::table('users')
            ->join('task_user','users.id','=','task_user.user_id')
            ->join('tasks','task_user.task_id','=','tasks.id')
            ->where('tasks.deleted', false)
            ->select('users.id','users.name',
                DB::raw('COUNT(tasks.id) as total'),
                DB::raw("SUM(CASE WHEN tasks.status='pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN tasks.status='follow_up' THEN 1 ELSE 0 END) as follow_up"),
                DB::raw("SUM(CASE WHEN tasks.status='completed' THEN 1 ELSE 0 END) as completed"),
                DB::raw("(SELECT COUNT(*) FROM tasks t2 WHERE t2.completed_by = users.id AND t2.deleted = 0) as completed_by_user"),
                DB::raw("(SELECT COUNT(*) FROM tasks t3 WHERE t3.completed_by = users.id AND t3.deleted = 0 AND DATE(t3.completed_at) = '{$today}') as completed_by_user_today"),
                DB::raw("SUM(CASE WHEN tasks.status='completed' AND DATE(tasks.completed_at) = '{$today}' THEN 1 ELSE 0 END) as completed_today")
            )
            ->groupBy('users.id','users.name')
            ->orderByRaw("(SELECT COUNT(*) FROM tasks t2 WHERE t2.completed_by = users.id AND t2.deleted = 0) DESC")
            ->orderBy('users.name')
            ->get();
        $rank = 1;
        $this->userTaskStats = $rankedRows->map(function($r) use (&$rank){
            $pct = $r->total ? round(($r->completed / $r->total)*100) : 0;
            return [
                'rank' => $rank++,
                'user_id' => $r->id,
                'name' => $r->name,
                'total' => (int)$r->total,
                'pending' => (int)$r->pending,
                'follow_up' => (int)$r->follow_up,
                'completed' => (int)$r->completed,
                'completed_today' => (int)$r->completed_today,
                'completed_by_user' => (int)$r->completed_by_user,
                'completed_by_user_today' => (int)$r->completed_by_user_today,
                'completed_pct' => $pct,
            ];
        })->toArray();
    }

    public function render()
    {
         $this->authorize('dashboard-render');
        return view('livewire.dashboard-overview', [
            'totalPopulation'   => $this->totalPopulation,
            'maleCount'         => $this->maleCount,
            'femaleCount'       => $this->femaleCount,
            'islandLabels'      => $this->islandLabels,
            'islandMaleCounts'  => $this->islandMaleCounts,
            'islandFemaleCounts' => $this->islandFemaleCounts,
            'islandTotals'      => $this->islandTotals,
            'taskTotal'         => $this->taskTotal,
            'taskPending'       => $this->taskPending,
            'taskFollowUp'      => $this->taskFollowUp,
            'taskCompleted'     => $this->taskCompleted,
            'userTaskStats'     => $this->userTaskStats,
        ])->layout('layouts.master');
    }
}
