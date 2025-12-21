<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Models\Directory;
use App\Models\Election;
use App\Models\Form;
use App\Models\FormQuestion;
use App\Models\FormSubmissionAnswer;
use App\Models\FormQuestionOption;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Arr;

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

    public $pledgeLabels = [];
    public $provYes = []; public $provNo = []; public $provUndecided = []; public $provPending = [];
    public $finalYes = []; public $finalNo = []; public $finalUndecided = []; public $finalPending = [];
    public $pledgeElectionId = null;

    public $forms = [];
    public $selectedFormId = null;
    public $selectedQuestionId = null;

    public $fsLabels = []; // sub consite codes
    public $fsSeries = []; // [{label:'Option A', data:[...]}]
    public $fsAllCharts = []; // NEW: [{questionId,text,labels,series}]
    public $fsBySubCharts = []; // NEW: [{subCode, questions:[{text, labels(options), data(counts)}]}]

    public function mount(): void
    {
        // Do not block with authorize to avoid zeros; authorize in route/middleware
        $this->computeStats();
        $this->pledgeElectionId = Election::orderBy('start_date','desc')->value('id');
        $this->computePledgeBySubConsite();

        $this->forms = Form::orderBy('title')->get(['id','title']);
        // Preselect first form/question
        $firstForm = $this->forms->first();
        if ($firstForm) {
            $this->selectedFormId = $firstForm->id;
            $firstQuestion = FormQuestion::where('form_id',$firstForm->id)
                ->whereIn('type', ['dropdown','radio'])
                ->orderBy('position')
                ->first(['id','question_text']);
            if ($firstQuestion) { $this->selectedQuestionId = $firstQuestion->id; }
        }
        $this->computeFormSubmissionChart();
        // Dispatch initial chart data to ensure JS initializes
        $this->dispatch('admin-form-chart-update', ['labels'=>$this->fsLabels, 'series'=>$this->fsSeries]);
    }

    // Explicit refresh method to recompute and dispatch
    public function refreshFormChart(): void
    {
        $this->computeFormSubmissionChart();
        $this->dispatch('admin-form-chart-update', ['labels'=>$this->fsLabels, 'series'=>$this->fsSeries]);
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
    }

    private function computePledgeBySubConsite(): void
    {
        $eId = $this->pledgeElectionId;
        // Provisional per-user: join voter_provisional_user_pledges
        $rowsProv = DB::table('sub_consites as s')
            ->leftJoin('directories as d','d.sub_consite_id','=','s.id')
            ->leftJoin('voter_provisional_user_pledges as vpup', function($join) use ($eId){
                $join->on('vpup.directory_id','=','d.id');
                if ($eId) { $join->where('vpup.election_id',$eId); }
            })
            ->select('s.code')
            ->selectRaw("SUM(CASE WHEN LOWER(vpup.status)='yes' THEN 1 ELSE 0 END) as yes")
            ->selectRaw("SUM(CASE WHEN LOWER(vpup.status)='no' THEN 1 ELSE 0 END) as no")
            ->selectRaw("SUM(CASE WHEN LOWER(vpup.status)='neutral' THEN 1 ELSE 0 END) as undecided")
            ->selectRaw("COUNT(DISTINCT CASE WHEN d.status='Active' THEN d.id END) as active_dirs")
            ->selectRaw("COUNT(DISTINCT CASE WHEN vpup.id IS NOT NULL THEN d.id END) as pledged_dirs")
            ->groupBy('s.code')
            ->orderBy('s.code')
            ->get();
        $this->pledgeLabels = $rowsProv->pluck('code')->toArray();
        $this->provYes = $rowsProv->pluck('yes')->map(fn($v)=> (int)$v)->toArray();
        $this->provNo = $rowsProv->pluck('no')->map(fn($v)=> (int)$v)->toArray();
        $this->provUndecided = $rowsProv->pluck('undecided')->map(fn($v)=> (int)$v)->toArray();
        // Pending = Active dirs in sub - pledged_dirs (any user)
        $this->provPending = $rowsProv->map(fn($r)=> max(0, (int)($r->active_dirs ?? 0) - (int)($r->pledged_dirs ?? 0)))->toArray();

        // Final simplified (unchanged)
        $rowsFinal = DB::table('sub_consites as s')
            ->leftJoin('directories as d','d.sub_consite_id','=','s.id')
            ->leftJoin('voter_pledges as vp', function($join){
                $join->on('vp.directory_id','=','d.id')
                     ->where('vp.type','final');
                if ($this->pledgeElectionId) { $join->where('vp.election_id',$this->pledgeElectionId); }
            })
            ->select('s.code')
            ->selectRaw("SUM(CASE WHEN LOWER(vp.status)='yes' THEN 1 ELSE 0 END) as yes")
            ->selectRaw("SUM(CASE WHEN LOWER(vp.status)='no' THEN 1 ELSE 0 END) as no")
            ->selectRaw("SUM(CASE WHEN LOWER(vp.status)='neutral' THEN 1 ELSE 0 END) as undecided")
            ->selectRaw("COUNT(DISTINCT CASE WHEN d.status='Active' THEN d.id END) as active_dirs")
            ->selectRaw("COUNT(DISTINCT CASE WHEN vp.id IS NOT NULL THEN d.id END) as pledged_dirs")
            ->groupBy('s.code')
            ->orderBy('s.code')
            ->get();
        $this->finalYes = $rowsFinal->pluck('yes')->map(fn($v)=> (int)$v)->toArray();
        $this->finalNo = $rowsFinal->pluck('no')->map(fn($v)=> (int)$v)->toArray();
        $this->finalUndecided = $rowsFinal->pluck('undecided')->map(fn($v)=> (int)$v)->toArray();
        $this->finalPending = $rowsFinal->map(fn($r)=> max(0, (int)($r->active_dirs ?? 0) - (int)($r->pledged_dirs ?? 0)))->toArray();
    }

    public function updatedSelectedFormId(): void
    {
        $q = FormQuestion::where('form_id',$this->selectedFormId)
            ->whereIn('type', ['dropdown','radio'])
            ->orderBy('position')
            ->first(['id']);
        $this->selectedQuestionId = optional($q)->id;
        $this->computeFormSubmissionChart();
    }

    public function updatedSelectedQuestionId(): void
    {
        $this->computeFormSubmissionChart();
    }

    private function computeFormSubmissionChart(): void
    {
        $this->fsLabels = []; $this->fsSeries = []; $this->fsAllCharts = []; $this->fsBySubCharts = [];
        if (! $this->selectedFormId) { $this->dispatch('admin-form-chart-update', ['labels'=>[], 'series'=>[]]); return; }

        $subs = \DB::table('sub_consites')->select('id','code')->orderBy('code')->get();
        $labelsBySub = $subs->pluck('code')->toArray();
        $indexById = $subs->pluck('code','id');
        $this->fsLabels = $labelsBySub;

        $questions = FormQuestion::where('form_id',$this->selectedFormId)
            ->whereIn('type', ['dropdown','radio','select'])
            ->orderBy('position')
            ->get(['id','question_text']);

        $subInit = [];
        foreach ($labelsBySub as $code) { $subInit[$code] = ['subCode'=>$code, 'questions'=>[]]; }

        foreach ($questions as $q) {
            $optMap = \App\Models\FormQuestionOption::where('form_question_id', $q->id)->pluck('label','value');
            $answers = \DB::table('form_submission_answers as fsa')
                ->join('form_submissions as fs', 'fs.id','=','fsa.form_submission_id')
                ->join('directories as d','d.id','=','fs.directory_id')
                ->where('fs.form_id', $this->selectedFormId)
                ->where('fsa.form_question_id', $q->id)
                ->select('d.sub_consite_id','fsa.value_text','fsa.value_text_dv')
                ->get();
            $matrix = []; // label => subCode => count
            foreach ($answers as $row) {
                $subId = $row->sub_consite_id; $subCode = $indexById[$subId] ?? null; if (! $subCode) continue;
                // Prefer Faruma (Dhivehi) label if present
                $labelDv = trim((string)($row->value_text_dv ?? ''));
                $value = trim((string)($row->value_text ?? ''));
                $label = $labelDv !== '' ? $labelDv : ((string)($optMap[$value] ?? $value ?: 'Other'));
                if (! isset($matrix[$label])) { $matrix[$label] = array_fill(0, count($labelsBySub), 0); }
                $pos = array_search($subCode, $labelsBySub, true);
                if ($pos !== false) { $matrix[$label][$pos]++; }
            }
            $colors = ['#3e97ff','#f6c000','#50cd89','#f1416c','#a1a5b7','#7239ea','#00a3ef','#ff6b6b'];
            $i = 0; $series = [];
            foreach ($matrix as $label => $data) { $series[] = [ 'label' => $label, 'data' => array_map(fn($v)=> (int)$v, $data), 'color' => $colors[$i % count($colors)] ]; $i++; }
            $this->fsAllCharts[] = [ 'questionId' => $q->id, 'text' => $q->question_text, 'labels' => $labelsBySub, 'series' => $series ];

            foreach ($labelsBySub as $idx => $subCode) {
                $optLabels = array_keys($matrix);
                $optCounts = [];
                foreach ($optLabels as $optLabel) { $optCounts[] = (int)($matrix[$optLabel][$idx] ?? 0); }
                $subInit[$subCode]['questions'][] = [ 'text' => $q->question_text, 'labels' => $optLabels, 'data' => $optCounts ];
            }
        }

        $this->fsBySubCharts = array_values($subInit);

        // Maintain backward-compat for single selection
        if ($this->selectedQuestionId) {
            $one = collect($this->fsAllCharts)->firstWhere('questionId', $this->selectedQuestionId);
            if ($one) { $this->fsSeries = $one['series']; $this->fsLabels = $one['labels']; }
        }

        // Dispatch update for current selection chart
        $this->dispatch('admin-form-chart-update', ['labels'=>$this->fsLabels, 'series'=>$this->fsSeries]);
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
            'pledgeLabels' => $this->pledgeLabels,
            'provYes' => $this->provYes,
            'provNo' => $this->provNo,
            'provUndecided' => $this->provUndecided,
            'provPending' => $this->provPending,
            'finalYes' => $this->finalYes,
            'finalNo' => $this->finalNo,
            'finalUndecided' => $this->finalUndecided,
            'finalPending' => $this->finalPending,
            'forms' => $this->forms,
            'selectedFormId' => $this->selectedFormId,
            'selectedQuestionId' => $this->selectedQuestionId,
            'fsLabels' => $this->fsLabels,
            'fsSeries' => $this->fsSeries,
            'fsAllCharts' => $this->fsAllCharts, // NEW
            'fsBySubCharts' => $this->fsBySubCharts, // NEW
        ])->layout('layouts.master');
    }
}
