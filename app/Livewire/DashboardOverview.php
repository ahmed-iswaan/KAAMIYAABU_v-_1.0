<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Directory;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\Election;
use App\Models\ElectionDirectoryCallStatus;

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

    // New: same datasets as admin dashboard
    public $activeDirectories = 0;
    public $directoriesWithNoTasks = 0;
    public $piePendingDirs = 0;
    public $pieFollowUpDirs = 0;
    public $pieCompletedDirs = 0;

    public $subConsiteLabels = [];
    public $subConsitePending = [];
    public $subConsiteFollowUp = [];
    public $subConsiteCompleted = [];
    public $subConsiteNoTask = [];

    // Call-center style totals (directories)
    public int $overviewTotal = 0;
    public int $overviewCompleted = 0;
    public int $overviewPending = 0;
    public int $overviewCompletedByMe = 0;

    // New: election call status pie totals (directories)
    public int $pieElectionPendingDirs = 0;
    public int $pieElectionCompletedDirs = 0;

    protected $listeners = ['taskChanged' => 'refreshStats'];

    public function mount()
    {
        $this->computeDirectoryStats();
        $this->computeTaskStats();
        $this->computeDashboardTaskDirectoryCharts();
        $this->computeOverviewCallCenterTotals();
        $this->computeElectionDirectoryPie();
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

        // Users performance (by election call status + user sub consites)
        $today = now()->toDateString();

        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        // Viewer can only see users in their allowed SubConsites
        $viewerAllowedSubConsiteIds = auth()->user()?->subConsites()->pluck('sub_consites.id')->all() ?? [];

        $userRows = DB::table('users')
            ->join('users_sub_consites as usc', 'usc.user_id', '=', 'users.id')
            ->join('directories as d', function($join){
                $join->on('d.sub_consite_id', '=', 'usc.sub_consite_id')
                    ->where('d.status', '=', 'Active');
            })
            ->leftJoin('election_directory_call_statuses as edcs', function($join) use ($activeElectionId){
                $join->on('edcs.directory_id', '=', 'd.id');
                if ($activeElectionId) {
                    $join->where('edcs.election_id', '=', (string) $activeElectionId);
                } else {
                    $join->whereRaw('1=0');
                }
            })
            ->when(count($viewerAllowedSubConsiteIds), function($q) use ($viewerAllowedSubConsiteIds){
                $q->whereIn('usc.sub_consite_id', $viewerAllowedSubConsiteIds);
            })
            ->select('users.id','users.name',
                DB::raw('COUNT(DISTINCT d.id) as total'),
                // Completed within assigned directories (by anyone)
                DB::raw("COUNT(DISTINCT CASE WHEN edcs.status = 'completed' THEN d.id END) as completed_assigned"),
                DB::raw("COUNT(DISTINCT CASE WHEN edcs.status = 'completed' AND DATE(edcs.completed_at) = '{$today}' THEN d.id END) as completed_assigned_today"),
                // Completed performed by this user
                DB::raw("SUM(CASE WHEN edcs.status = 'completed' AND edcs.updated_by = users.id THEN 1 ELSE 0 END) as completed_by_user"),
                DB::raw("SUM(CASE WHEN edcs.status = 'completed' AND edcs.updated_by = users.id AND DATE(edcs.completed_at) = '{$today}' THEN 1 ELSE 0 END) as completed_by_user_today")
            )
            ->groupBy('users.id','users.name')
            ->orderByRaw("COUNT(DISTINCT CASE WHEN edcs.status = 'completed' THEN d.id END) DESC")
            ->orderBy('users.name')
            ->get();

        $rank = 1;
        $this->userTaskStats = $userRows
            ->filter(fn($r) => (int)($r->completed_by_user ?? 0) > 0)
             ->map(function($r) use (&$rank){
                 $total = (int)($r->total ?? 0);
                 $completedAssigned = (int)($r->completed_assigned ?? 0);
                 $pending = max(0, $total - $completedAssigned);
                 $pct = $total ? round(($completedAssigned / $total) * 100) : 0;

                 return [
                     'rank' => $rank++,
                     'user_id' => $r->id,
                     'name' => $r->name,
                     'total' => $total,
                     'pending' => $pending,
                     'completed_assigned' => $completedAssigned,
                     'completed_assigned_today' => (int)($r->completed_assigned_today ?? 0),
                     'completed_by_user' => (int)($r->completed_by_user ?? 0),
                     'completed_by_user_today' => (int)($r->completed_by_user_today ?? 0),
                     'completed_pct' => $pct,
                 ];
             })->toArray();
    }

    public function refreshStats(): void
    {
        $this->computeTaskStats();
        $this->computeDashboardTaskDirectoryCharts();
        $this->computeOverviewCallCenterTotals();
        $this->computeElectionDirectoryPie();
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
                DB::raw("SUM(CASE WHEN tasks.status='completed' THEN 1 ELSE 0 END) as completed_assigned"),
                DB::raw("SUM(CASE WHEN tasks.status='completed' AND tasks.completed_by = users.id THEN 1 ELSE 0 END) as completed_by_user"),
                DB::raw("SUM(CASE WHEN tasks.status='completed' AND tasks.completed_by = users.id AND DATE(tasks.completed_at) = '{$today}' THEN 1 ELSE 0 END) as completed_by_user_today"),
                DB::raw("SUM(CASE WHEN tasks.status='completed' AND DATE(tasks.completed_at) = '{$today}' THEN 1 ELSE 0 END) as completed_assigned_today"),
                DB::raw("SUM(CASE WHEN tasks.status='follow_up' AND tasks.follow_up_by = users.id THEN 1 ELSE 0 END) as follow_up_by_user"),
                DB::raw("SUM(CASE WHEN tasks.status='follow_up' AND tasks.follow_up_by = users.id AND DATE(tasks.followup_at) = '{$today}' THEN 1 ELSE 0 END) as follow_up_by_user_today")
            )
            ->groupBy('users.id','users.name')
            ->orderByRaw("SUM(CASE WHEN tasks.status='completed' AND tasks.completed_by = users.id THEN 1 ELSE 0 END) DESC")
            ->orderBy('users.name')
            ->get();
        $rank = 1;
        $this->userTaskStats = $rankedRows->map(function($r) use (&$rank){
            $pct = $r->total ? round(($r->completed_assigned / $r->total)*100) : 0;
            return [
                'rank' => $rank++,
                'user_id' => $r->id,
                'name' => $r->name,
                'total' => (int)$r->total,
                'pending' => (int)$r->pending,
                'follow_up' => (int)$r->follow_up,
                'completed' => (int)$r->completed_assigned,
                'completed_today' => (int)$r->completed_assigned_today,
                'completed_by_user' => (int)$r->completed_by_user,
                'completed_by_user_today' => (int)$r->completed_by_user_today,
                'follow_up_by_user' => (int)$r->follow_up_by_user,
                'follow_up_by_user_today' => (int)$r->follow_up_by_user_today,
                'completed_pct' => $pct,
            ];
        })->toArray();
    }

    protected function computeDashboardTaskDirectoryCharts(): void
    {
        // Active directories
        $this->activeDirectories = (int) Directory::where('status','Active')->count();

        // Directories with no tasks
        $this->directoriesWithNoTasks = (int) Directory::where('status','Active')
            ->whereNotExists(function($q){
                $q->selectRaw(1)
                    ->from('tasks')
                    ->whereColumn('tasks.directory_id','directories.id')
                    ->where('tasks.deleted',false);
            })->count();

        // Latest task status per directory (mutually exclusive)
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

        // SubConsite pending/completed by current active election call status
        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        $rows = DB::table('sub_consites')
            ->leftJoin('directories', function($join){
                $join->on('directories.sub_consite_id','=','sub_consites.id')
                    ->where('directories.status', 'Active');
            })
            ->leftJoin('election_directory_call_statuses as edcs', function($join) use ($activeElectionId){
                $join->on('edcs.directory_id','=','directories.id');
                if ($activeElectionId) {
                    $join->where('edcs.election_id', '=', (string) $activeElectionId);
                } else {
                    // If no active election, prevent matching any rows
                    $join->whereRaw('1=0');
                }
            })
            ->select('sub_consites.id','sub_consites.code')
            ->selectRaw("COUNT(DISTINCT directories.id) as active_dirs")
            ->selectRaw("COUNT(DISTINCT edcs.directory_id) as status_dirs")
            ->selectRaw("COALESCE(SUM(CASE WHEN edcs.status = 'completed' THEN 1 ELSE 0 END),0) as completed")
            ->groupBy('sub_consites.id','sub_consites.code')
            ->orderBy('sub_consites.code')
            ->get();

        $this->subConsiteLabels = $rows->pluck('code')->toArray();
        $this->subConsiteCompleted = $rows->pluck('completed')->map(fn($v)=> (int)$v)->toArray();

        // Pending = active - completed (Call Center definition)
        $this->subConsitePending = $rows->map(function($r){
            $active = (int)($r->active_dirs ?? 0);
            $completed = (int)($r->completed ?? 0);
            return max(0, $active - $completed);
        })->toArray();

        // Keep these arrays but set to 0 so existing JS doesn't break if still referenced
        $this->subConsiteFollowUp = array_fill(0, count($this->subConsiteLabels), 0);
        $this->subConsiteNoTask = array_fill(0, count($this->subConsiteLabels), 0);
    }

    protected function computeOverviewCallCenterTotals(): void
    {
        $this->overviewTotal = 0;
        $this->overviewCompleted = 0;
        $this->overviewPending = 0;
        $this->overviewCompletedByMe = 0;

        $user = auth()->user();
        if (!$user) return;

        // Match Call Center logic: user-accessible directories are based on user's SubConsites
        $allowedSubConsiteIds = $user->subConsites()->pluck('sub_consites.id')->all();
        if (!count($allowedSubConsiteIds)) return;

        $directoryIds = Directory::query()
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowedSubConsiteIds)
            ->pluck('id')
            ->map(fn($v) => (string) $v)
            ->all();

        $this->overviewTotal = count($directoryIds);
        if (!$this->overviewTotal) return;

        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        if (!$activeElectionId) {
            // No active election -> everything is pending
            $this->overviewPending = $this->overviewTotal;
            return;
        }

        $statuses = ElectionDirectoryCallStatus::query()
            ->where('election_id', (string) $activeElectionId)
            ->whereIn('directory_id', $directoryIds)
            ->get(['directory_id', 'status', 'updated_by']);

        $this->overviewCompleted = $statuses->where('status', 'completed')->count();
        $this->overviewCompletedByMe = $statuses
            ->where('status', 'completed')
            ->where('updated_by', auth()->id())
            ->count();

        $this->overviewPending = max(0, $this->overviewTotal - $this->overviewCompleted);
    }

    protected function computeElectionDirectoryPie(): void
    {
        $this->pieElectionPendingDirs = 0;
        $this->pieElectionCompletedDirs = 0;

        $user = auth()->user();
        if (!$user) return;

        $allowedSubConsiteIds = $user->subConsites()->pluck('sub_consites.id')->all();
        if (!count($allowedSubConsiteIds)) return;

        $directoryIds = Directory::query()
            ->where('status', 'Active')
            ->whereIn('sub_consite_id', $allowedSubConsiteIds)
            ->pluck('id')
            ->map(fn($v) => (string) $v)
            ->all();

        $total = count($directoryIds);
        if (!$total) return;

        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        if (!$activeElectionId) {
            $this->pieElectionPendingDirs = $total;
            return;
        }

        $completed = ElectionDirectoryCallStatus::query()
            ->where('election_id', (string) $activeElectionId)
            ->whereIn('directory_id', $directoryIds)
            ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
            ->count();

        $this->pieElectionCompletedDirs = (int) $completed;
        $this->pieElectionPendingDirs = max(0, $total - $this->pieElectionCompletedDirs);
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
            'activeDirectories' => $this->activeDirectories,
            'directoriesWithNoTasks' => $this->directoriesWithNoTasks,
            'piePendingDirs' => $this->piePendingDirs,
            'pieFollowUpDirs' => $this->pieFollowUpDirs,
            'pieCompletedDirs' => $this->pieCompletedDirs,
            'subConsiteLabels' => $this->subConsiteLabels,
            'subConsitePending' => $this->subConsitePending,
            'subConsiteFollowUp' => $this->subConsiteFollowUp,
            'subConsiteCompleted' => $this->subConsiteCompleted,
            'subConsiteNoTask' => $this->subConsiteNoTask,
            'overviewTotal' => $this->overviewTotal,
            'overviewCompleted' => $this->overviewCompleted,
            'overviewPending' => $this->overviewPending,
            'overviewCompletedByMe' => $this->overviewCompletedByMe,
            'pieElectionPendingDirs' => $this->pieElectionPendingDirs,
            'pieElectionCompletedDirs' => $this->pieElectionCompletedDirs,
        ])->layout('layouts.master');
    }
}
