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
use App\Models\ElectionDirectoryCallStatus;
use App\Models\CallCenterForm;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    // Current active election (call status) - same datasets as /dashboard
    public int $pieElectionPendingDirs = 0;
    public int $pieElectionCompletedDirs = 0;

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

    // Per-question totals pies: [{questionId, text, labels, counts}]
    public $formTotalsPies = [];

    public $q1PieLabels = [];
    public $q1PieCounts = [];

    public $q3PieLabels = [];
    public $q3PieCounts = [];

    public $provPledged = [];
    public $provNotPledged = [];

    // Pending/Completed by SubConsite (current active election)
    public $dashSubConsiteLabels = [];
    public $dashSubConsitePending = [];
    public $dashSubConsiteCompleted = [];

    // Replace follow-up card with daily completed
    public $tasksCompletedToday = 0;

    // Directory status totals
    public $directoriesActive = 0;
    public $directoriesInactive = 0;

    // Election status totals (directories)
    public int $directoriesPendingTotal = 0;
    public int $directoriesCompletedTotal = 0;
    public int $directoriesCompletedTodayTotal = 0;

    // Q1/Q3/Q4/Q5 answers by SubConsite (stacked bars per question)
    public $qsBySubCharts = []; // [{position, questionId, text, labels(subs), series:[{label,color,data[]}]]

    // Filters for call center charts
    public $ccElectionId = null;
    public $ccSubConsiteId = null;

    public $ccAvailableElections = [];
    public $ccAvailableSubConsites = [];

    // Users performance (active election call status)
    public array $userPerformanceRows = [];
    public bool $showAllUsersPerformance = false;
    public array $expandedUserIds = []; // [userId => true]
    public array $userDailyStats = []; // [userId => [['date'=>Y-m-d,'completed'=>n,'attempts'=>n],...]]

    // CSV export (daily)
    public ?int $selectedUserPerformanceCsvUserId = null;
    public array $userPerformanceUsersForSelect = []; // [['id'=>..,'name'=>..], ...]

    public function mount(): void
    {
        // Do not block with authorize to avoid zeros; authorize in route/middleware
        $this->computeStats();
        $this->computeElectionDirectoryPie();
        $this->computeElectionPendingCompletedBySubConsite();

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
        $this->computeFormTotalsPies();
        $this->computeQ1Q3TotalsPies();
        $this->computeQPositionsBySubConsiteCharts([1,3,4,5]);
        // Dispatch initial chart data to ensure JS initializes
        $this->dispatch('admin-form-chart-update', ['labels'=>$this->fsLabels, 'series'=>$this->fsSeries]);

        $this->ccAvailableElections = Election::orderBy('start_date','desc')->get(['id','name','start_date']);
        $this->ccAvailableSubConsites = DB::table('sub_consites')->orderBy('code')->get(['id','code']);

        $this->ccElectionId = Election::query()->where('status', Election::STATUS_ACTIVE)->value('id');
        $this->ccSubConsiteId = null;

        $this->computeQPositionsBySubConsiteCharts([1,3,4,5]);

        $this->computeUsersPerformance();

        // build user select list for daily CSV
        $this->userPerformanceUsersForSelect = collect($this->userPerformanceRows ?? [])
            ->map(fn($r) => ['id' => (int)$r['user_id'], 'name' => (string)$r['name']])
            ->values()
            ->toArray();

        $this->selectedUserPerformanceCsvUserId = (int) (collect($this->userPerformanceUsersForSelect)->first()['id'] ?? 0) ?: null;
    }

    // Explicit refresh method to recompute and dispatch
    public function refreshFormChart(): void
    {
        $this->computeFormSubmissionChart();
        $this->dispatch('admin-form-chart-update', ['labels'=>$this->fsLabels, 'series'=>$this->fsSeries]);
    }

    private function computeStats(): void
    {
        // Directory status totals (from current active election call status)
        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        $totalActiveDirs = (int) Directory::query()->where('status', 'Active')->count();

        // Compute election status totals (directories) for top cards
        $this->activeDirectories = $totalActiveDirs;

        if (!$activeElectionId) {
            $this->directoriesCompletedTotal = 0;
            $this->directoriesPendingTotal = $totalActiveDirs;
            $this->directoriesCompletedTodayTotal = 0;
        } else {
            $completedAll = (int) ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $activeElectionId)
                ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                ->distinct('directory_id')
                ->count('directory_id');

            $completedToday = (int) ElectionDirectoryCallStatus::query()
                ->where('election_id', (string) $activeElectionId)
                ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
                ->whereDate('completed_at', now()->toDateString())
                ->distinct('directory_id')
                ->count('directory_id');

            $this->directoriesCompletedTotal = $completedAll;
            $this->directoriesPendingTotal = max(0, $totalActiveDirs - $completedAll);
            $this->directoriesCompletedTodayTotal = $completedToday;
        }

        // Backward compatible fields used by the existing blade (now represent pending/completed)
        $this->directoriesActive = $this->directoriesPendingTotal;
        $this->directoriesInactive = $this->directoriesCompletedTotal;

        // Keep task aggregates (other parts of admin dashboard still use them)
        $taskAgg = DB::table('tasks')
            ->where('deleted', false)
            ->selectRaw("SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status='follow_up' THEN 1 ELSE 0 END) as follow_up")
            ->selectRaw("SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status='completed' AND DATE(updated_at)=? THEN 1 ELSE 0 END) as completed_today", [now()->toDateString()])
            ->first();

        $this->tasksPending = (int)($taskAgg->pending ?? 0);
        $this->tasksFollowUp = (int)($taskAgg->follow_up ?? 0);
        $this->tasksCompleted = (int)($taskAgg->completed ?? 0);

        // For the top card, use election-directory daily completed, but keep this property for other potential uses.
        $this->tasksCompletedToday = (int) $this->directoriesCompletedTodayTotal;

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

        $this->provPledged = $rowsProv->pluck('pledged_dirs')->map(fn($v)=> (int)$v)->toArray();
        $this->provNotPledged = $rowsProv->map(fn($r)=> max(0, (int)($r->active_dirs ?? 0) - (int)($r->pledged_dirs ?? 0)))->toArray();

        // Pending = Active dirs in sub - pledged_dirs (any user)
        $this->provPending = $this->provNotPledged;

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
        $this->computeFormTotalsPies();
        $this->computeQPositionsBySubConsiteCharts([1,3,4,5]);
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
            // Map option values to labels (set of valid values)
            $optMap = \App\Models\FormQuestionOption::where('form_question_id', $q->id)->pluck('label','value');
            $validValues = array_keys($optMap->toArray());
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
                $value = trim((string)($row->value_text ?? ''));
                // Only count if the answer matches a defined option
                if ($value === '' || ! in_array($value, $validValues, true)) { continue; }
                // Prefer Faruma (Dhivehi) label if provided in value_text_dv, else mapped label
                $labelDv = trim((string)($row->value_text_dv ?? ''));
                $label = $labelDv !== '' ? $labelDv : (string)($optMap[$value] ?? $value);
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

    private function computeFormTotalsPies(): void
    {
        $this->formTotalsPies = [];

        $formId = $this->selectedFormId;
        if (!$formId) return;

        $questions = FormQuestion::where('form_id', $formId)
            ->whereIn('type', ['dropdown','radio','select'])
            ->orderBy('position')
            ->get(['id','question_text']);

        foreach ($questions as $q) {
            $optMap = FormQuestionOption::where('form_question_id', $q->id)
                ->pluck('label','value')
                ->toArray();
            $validValues = array_keys($optMap);

            $rows = DB::table('form_submission_answers as a')
                ->join('form_submissions as s', 's.id', '=', 'a.form_submission_id')
                ->where('s.form_id', $formId)
                ->where('a.form_question_id', $q->id)
                ->select('a.value_text','a.value_text_dv')
                ->get();

            $counts = [];
            foreach ($rows as $r) {
                $value = trim((string)($r->value_text ?? ''));
                if ($value === '') continue;

                // Only count defined options if options exist
                if (!empty($validValues) && !in_array($value, $validValues, true)) {
                    continue;
                }

                $labelDv = trim((string)($r->value_text_dv ?? ''));
                $label = $labelDv !== '' ? $labelDv : (string)($optMap[$value] ?? $value);
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }

            if (empty($counts)) continue;

            arsort($counts);
            $this->formTotalsPies[] = [
                'questionId' => (string)$q->id,
                'text' => $q->question_text,
                'labels' => array_keys($counts),
                'counts' => array_values($counts),
            ];
        }
    }

    private function computeQ1Q3TotalsPies(): void
    {
        $this->q1PieLabels = $this->q1PieCounts = [];
        $this->q3PieLabels = $this->q3PieCounts = [];

        $formId = $this->selectedFormId ?: (Form::orderBy('title')->value('id'));
        if (!$formId) return;

        $q1 = FormQuestion::where('form_id', $formId)->where('position', 1)->first(['id']);
        $q3 = FormQuestion::where('form_id', $formId)->where('position', 3)->first(['id']);
        if (!$q1 && !$q3) return;

        $build = function($questionId) use ($formId) {
            if (!$questionId) return [[],[]];

            $optMap = FormQuestionOption::where('form_question_id', $questionId)
                ->pluck('label','value')
                ->toArray();

            $rows = DB::table('form_submission_answers as a')
                ->join('form_submissions as s', 's.id', '=', 'a.form_submission_id')
                ->where('s.form_id', $formId)
                ->where('a.form_question_id', $questionId)
                ->select('a.value_text','a.value_text_dv')
                ->get();

            $counts = [];
            foreach ($rows as $r) {
                $value = trim((string)($r->value_text ?? ''));
                if ($value === '') continue;

                $labelDv = trim((string)($r->value_text_dv ?? ''));
                $label = $labelDv !== '' ? $labelDv : (string)($optMap[$value] ?? $value);
                $counts[$label] = ($counts[$label] ?? 0) + 1;
            }

            arsort($counts);
            return [array_keys($counts), array_values($counts)];
        };

        [$this->q1PieLabels, $this->q1PieCounts] = $build($q1?->id);
        [$this->q3PieLabels, $this->q3PieCounts] = $build($q3?->id);
    }

    private function computeQPositionsBySubConsiteCharts(array $positions): void
    {
        // call center questions live in call_center_forms
        $this->qsBySubCharts = [];

        $electionId = $this->ccElectionId ?: Election::query()->where('status', Election::STATUS_ACTIVE)->value('id');
        if (!$electionId) return;

        $subs = DB::table('sub_consites')->select('id','code')->orderBy('code')->get();
        $subCodes = $subs->pluck('code')->toArray();
        $codeById = $subs->pluck('code','id')->toArray();

        // If filtering to one SubConsite, only render that one label
        if ($this->ccSubConsiteId) {
            $selectedCode = $codeById[$this->ccSubConsiteId] ?? null;
            if ($selectedCode) {
                $subCodes = [$selectedCode];
            }
        }

        $palette = ['#3e97ff','#50cd89','#f6c000','#f1416c','#7239ea','#00a3ef','#a1a5b7','#181c32'];

        $questions = [
            1 => ['field' => 'q1_performance', 'text' => 'Performance'],
            3 => ['field' => 'q3_support', 'text' => 'Support'],
            4 => ['field' => 'q4_voting_area', 'text' => 'Voting area'],
            5 => ['field' => 'q5_help_needed', 'text' => 'Help needed'],
        ];

        foreach ($questions as $pos => $meta) {
            $rows = DB::table('call_center_forms as ccf')
                ->join('directories as d', 'd.id', '=', 'ccf.directory_id')
                ->where('ccf.election_id', (string) $electionId)
                ->when($this->ccSubConsiteId, function($q){
                    $q->where('d.sub_consite_id', $this->ccSubConsiteId);
                })
                ->whereNotNull('d.sub_consite_id')
                ->select('d.sub_consite_id', 'ccf.' . $meta['field'] . ' as answer')
                ->get();

            // counts[label][subCode]
            $counts = [];
            foreach ($rows as $r) {
                $value = trim((string)($r->answer ?? ''));
                if ($value === '') continue;
                $subCode = $codeById[$r->sub_consite_id] ?? null;
                if (!$subCode) continue;
                $label = $value;
                $counts[$label] = $counts[$label] ?? [];
                $counts[$label][$subCode] = ($counts[$label][$subCode] ?? 0) + 1;
            }

            if (empty($counts)) continue;

            $totals = [];
            foreach ($counts as $label => $bySub) {
                $totals[$label] = array_sum($bySub);
            }
            arsort($totals);
            $optionLabels = array_keys($totals);

            $series = [];
            foreach ($optionLabels as $i => $label) {
                $data = [];
                foreach ($subCodes as $subCode) {
                    $data[] = (int)($counts[$label][$subCode] ?? 0);
                }
                $series[] = [
                    'label' => $label,
                    'color' => $palette[$i % count($palette)],
                    'data' => $data,
                ];
            }

            $this->qsBySubCharts[] = [
                'position' => (int) $pos,
                'questionId' => (string) $pos,
                'text' => $meta['text'],
                'labels' => $subCodes,
                'series' => $series,
            ];
        }
    }

    protected function computeElectionDirectoryPie(): void
    {
        $this->pieElectionPendingDirs = 0;
        $this->pieElectionCompletedDirs = 0;

        $activeTotal = (int) Directory::query()->where('status', 'Active')->count();
        if (!$activeTotal) return;

        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        if (!$activeElectionId) {
            $this->pieElectionPendingDirs = $activeTotal;
            return;
        }

        $completed = ElectionDirectoryCallStatus::query()
            ->where('election_id', (string) $activeElectionId)
            ->where('status', ElectionDirectoryCallStatus::STATUS_COMPLETED)
            ->distinct('directory_id')
            ->count('directory_id');

        $this->pieElectionCompletedDirs = (int) $completed;
        $this->pieElectionPendingDirs = max(0, $activeTotal - $this->pieElectionCompletedDirs);
    }

    protected function computeElectionPendingCompletedBySubConsite(): void
    {
        $this->dashSubConsiteLabels = [];
        $this->dashSubConsitePending = [];
        $this->dashSubConsiteCompleted = [];

        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');

        $subs = DB::table('sub_consites')
            ->leftJoin('directories as d', function($join){
                $join->on('d.sub_consite_id', '=', 'sub_consites.id')
                    ->where('d.status', '=', 'Active');
            })
            ->leftJoin('election_directory_call_statuses as edcs', function($join) use ($activeElectionId){
                $join->on('edcs.directory_id', '=', 'd.id');
                if ($activeElectionId) {
                    $join->where('edcs.election_id', '=', (string) $activeElectionId);
                } else {
                    // No active election -> prevent matches
                    $join->whereRaw('1=0');
                }
            })
            ->select('sub_consites.code')
            ->selectRaw('COUNT(DISTINCT d.id) as total_active')
            ->selectRaw("COUNT(DISTINCT CASE WHEN edcs.status = 'completed' THEN d.id END) as completed")
            ->groupBy('sub_consites.code')
            ->orderBy('sub_consites.code')
            ->get();

        $this->dashSubConsiteLabels = $subs->pluck('code')->toArray();
        $this->dashSubConsiteCompleted = $subs->pluck('completed')->map(fn($v)=> (int)$v)->toArray();
        $this->dashSubConsitePending = $subs->map(function($r){
            $t = (int)($r->total_active ?? 0);
            $c = (int)($r->completed ?? 0);
            return max(0, $t - $c);
        })->toArray();
    }

    public function toggleShowAllUsersPerformance(): void
    {
        $this->showAllUsersPerformance = !$this->showAllUsersPerformance;
        $this->computeUsersPerformance();

        // refresh select options to match current list
        $this->userPerformanceUsersForSelect = collect($this->userPerformanceRows ?? [])
            ->map(fn($r) => ['id' => (int)$r['user_id'], 'name' => (string)$r['name']])
            ->values()
            ->toArray();

        if ($this->selectedUserPerformanceCsvUserId === null || !collect($this->userPerformanceUsersForSelect)->contains('id', $this->selectedUserPerformanceCsvUserId)) {
            $this->selectedUserPerformanceCsvUserId = (int) (collect($this->userPerformanceUsersForSelect)->first()['id'] ?? 0) ?: null;
        }
    }

    public function downloadUsersPerformanceCsv(): StreamedResponse
    {
        if (empty($this->userPerformanceRows)) {
            $this->computeUsersPerformance();
        }

        $filename = 'users_performance_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['User', 'Completed', 'Attempts']);

            foreach (($this->userPerformanceRows ?? []) as $row) {
                fputcsv($out, [
                    (string)($row['name'] ?? ''),
                    (int)($row['completed'] ?? 0),
                    (int)($row['attempts'] ?? 0),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function downloadUserPerformanceDailyCsv(): StreamedResponse
    {
        $userId = (int) ($this->selectedUserPerformanceCsvUserId ?? 0);
        if ($userId <= 0) {
            abort(422, 'Select a user');
        }

        // Ensure daily stats exist for this user
        $this->loadUserDailyStats($userId);

        $userName = collect($this->userPerformanceRows ?? [])->firstWhere('user_id', $userId)['name']
            ?? DB::table('users')->where('id', $userId)->value('name')
            ?? ('user_' . $userId);

        $safeName = preg_replace('/[^A-Za-z0-9_\-]+/','_', (string)$userName);
        $filename = 'users_performance_daily_' . $safeName . '_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($userId, $userName) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, ['User', 'Date', 'Completed', 'Attempts']);

            foreach (($this->userDailyStats[$userId] ?? []) as $d) {
                fputcsv($out, [
                    (string)$userName,
                    (string)($d['date'] ?? ''),
                    (int)($d['completed'] ?? 0),
                    (int)($d['attempts'] ?? 0),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function computeUsersPerformance(): void
    {
        $this->userPerformanceRows = [];

        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');
        if (!$activeElectionId) return;

        $attemptsByUser = DB::table('election_directory_call_sub_statuses as edcss')
            ->where('edcss.election_id', (string) $activeElectionId)
            ->select('edcss.updated_by')
            ->selectRaw('COUNT(DISTINCT CONCAT(edcss.directory_id, ":", edcss.attempt)) as attempts')
            ->groupBy('edcss.updated_by');

        $completedByUser = DB::table('election_directory_call_statuses as edcs')
            ->where('edcs.election_id', (string) $activeElectionId)
            ->where('edcs.status', 'completed')
            ->select('edcs.updated_by')
            ->selectRaw('COUNT(edcs.id) as completed')
            ->groupBy('edcs.updated_by');

        $rows = DB::table('users as u')
            ->leftJoinSub($attemptsByUser, 'a', function($join){
                $join->on('a.updated_by', '=', 'u.id');
            })
            ->leftJoinSub($completedByUser, 'c', function($join){
                $join->on('c.updated_by', '=', 'u.id');
            })
            ->select('u.id','u.name')
            ->selectRaw('COALESCE(c.completed, 0) as completed')
            ->selectRaw('COALESCE(a.attempts, 0) as attempts')
            ->orderByRaw('COALESCE(c.completed, 0) DESC')
            ->orderBy('u.name')
            ->get();

        if (!$this->showAllUsersPerformance) {
            $rows = $rows->filter(fn($r) => ((int)($r->attempts ?? 0)) > 0 || ((int)($r->completed ?? 0)) > 0);
        }

        $rank = 1;
        $this->userPerformanceRows = $rows->map(function($r) use (&$rank){
            return [
                'rank' => $rank++,
                'user_id' => (int)$r->id,
                'name' => $r->name,
                'completed' => (int)($r->completed ?? 0),
                'attempts' => (int)($r->attempts ?? 0),
            ];
        })->toArray();
    }

    protected function loadUserDailyStats(int $userId): void
    {
        $activeElectionId = Election::query()
            ->where('status', Election::STATUS_ACTIVE)
            ->value('id');
        if (!$activeElectionId) return;

        // IMPORTANT:
        // - We keep the UI behavior "2" (show only days with completed > 0 in the table).
        // - Daily attempts can still be computed correctly across all days.
        // - To avoid mismatches caused by edits to the same (directory_id,attempt) on later days,
        //   attribute each attempt to the FIRST date it appeared for that user.

        $attempts = DB::table('election_directory_call_sub_statuses as edcss')
            ->where('edcss.election_id', (string) $activeElectionId)
            ->where('edcss.updated_by', $userId)
            ->selectRaw('DATE(MIN(edcss.updated_at)) as d')
            ->selectRaw('COUNT(*) as attempts')
            ->groupBy('edcss.directory_id', 'edcss.attempt');

        // Completed daily should follow the semantic completed timestamp when present.
        $completed = DB::table('election_directory_call_statuses as edcs')
            ->where('edcs.election_id', (string) $activeElectionId)
            ->where('edcs.updated_by', $userId)
            ->where('edcs.status', 'completed')
            ->selectRaw('DATE(COALESCE(edcs.completed_at, edcs.updated_at)) as d')
            ->selectRaw('COUNT(*) as completed')
            ->groupBy(DB::raw('DATE(COALESCE(edcs.completed_at, edcs.updated_at))'));

        // Build distinct date list (single column only)
        $attemptDates = DB::query()->fromSub(
                DB::table('election_directory_call_sub_statuses as edcss')
                    ->where('edcss.election_id', (string) $activeElectionId)
                    ->where('edcss.updated_by', $userId)
                    ->select('edcss.directory_id', 'edcss.attempt', 'edcss.updated_at'),
                'x'
            )
            ->selectRaw('DISTINCT DATE(MIN(x.updated_at)) as d')
            ->groupBy('x.directory_id', 'x.attempt');

        $completeDates = DB::table('election_directory_call_statuses as edcs')
            ->where('edcs.election_id', (string) $activeElectionId)
            ->where('edcs.updated_by', $userId)
            ->where('edcs.status', 'completed')
            ->selectRaw('DISTINCT DATE(COALESCE(edcs.completed_at, edcs.updated_at)) as d');

        $datesUnion = $attemptDates->union($completeDates);

        $rows = DB::query()->fromSub($datesUnion, 'dd')
            ->leftJoinSub(
                DB::query()->fromSub($attempts, 'att')
                    ->select('att.d')
                    ->selectRaw('SUM(att.attempts) as attempts')
                    ->groupBy('att.d'),
                'a',
                function ($join) {
                    $join->on('a.d', '=', 'dd.d');
                }
            )
            ->leftJoinSub($completed, 'c', function($join){
                $join->on('c.d', '=', 'dd.d');
            })
            ->select('dd.d')
            ->selectRaw('COALESCE(c.completed, 0) as completed')
            ->selectRaw('COALESCE(a.attempts, 0) as attempts')
            ->orderBy('dd.d', 'desc')
            ->get();

        $this->userDailyStats[$userId] = $rows->map(fn($r) => [
            'date' => (string)$r->d,
            'completed' => (int)($r->completed ?? 0),
            'attempts' => (int)($r->attempts ?? 0),
        ])->toArray();
    }

    public function render()
    {
        return view('livewire.admin.admin-dashboard',[
            'activeDirectories' => $this->activeDirectories,
            'tasksPending' => $this->tasksPending,
            'tasksCompleted' => $this->tasksCompleted,
            'directoriesWithNoTasks' => $this->directoriesWithNoTasks,
            // Pie segments
            'piePendingDirs' => $this->piePendingDirs,
            'pieFollowUpDirs' => $this->pieFollowUpDirs,
            'pieCompletedDirs' => $this->pieCompletedDirs,
            'pieElectionPendingDirs' => $this->pieElectionPendingDirs,
            'pieElectionCompletedDirs' => $this->pieElectionCompletedDirs,
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
            'fsAllCharts' => $this->fsAllCharts,
            'fsBySubCharts' => $this->fsBySubCharts,
            'formTotalsPies' => $this->formTotalsPies,
            'dashSubConsiteLabels' => $this->dashSubConsiteLabels,
            'dashSubConsitePending' => $this->dashSubConsitePending,
            'dashSubConsiteCompleted' => $this->dashSubConsiteCompleted,
            'tasksCompletedToday' => $this->tasksCompletedToday,
            'directoriesActive' => $this->directoriesActive,
            'directoriesInactive' => $this->directoriesInactive,
            'directoriesPendingTotal' => $this->directoriesPendingTotal,
            'directoriesCompletedTotal' => $this->directoriesCompletedTotal,
            'directoriesCompletedTodayTotal' => $this->directoriesCompletedTodayTotal,
            'qsBySubCharts' => $this->qsBySubCharts,
            'ccAvailableElections' => $this->ccAvailableElections,
            'ccAvailableSubConsites' => $this->ccAvailableSubConsites,
            'ccElectionId' => $this->ccElectionId,
            'ccSubConsiteId' => $this->ccSubConsiteId,
            'userPerformanceRows' => $this->userPerformanceRows,
            'showAllUsersPerformance' => $this->showAllUsersPerformance,
            'expandedUserIds' => $this->expandedUserIds,
            'userDailyStats' => $this->userDailyStats,
        ])->layout('layouts.master');
    }
}
