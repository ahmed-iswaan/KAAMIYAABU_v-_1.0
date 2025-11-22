<?php

namespace App\Livewire\Forms;

use Livewire\Component;
use App\Models\{Form, FormSubmission, FormSubmissionAnswer, Directory};
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class FormResponses extends Component
{
    use AuthorizesRequests;

    public Form $form;
    public $questionStats = []; // per question option counts + respondents
    public $submissions = [];   // directories list
    public $totalSubmissions = 0;

    public function mount(Form $form)
    {
        $this->authorize('formslist-render'); // reuse permission (adjust if you create separate one)
        $this->form = $form->load(['questions.options']);
        $this->computeStats();
    }

    protected function computeStats(): void
    {
        $questionIds = $this->form->questions->pluck('id')->toArray();
        $answers = FormSubmissionAnswer::with(['submission.directory'])
            ->whereIn('form_question_id',$questionIds)
            ->get()
            ->groupBy('form_question_id');

        $stats = [];
        foreach ($this->form->questions as $q) {
            if(! in_array($q->type, ['radio','checkbox','select'])) { continue; }
            $qAnswers = $answers->get($q->id, collect());
            $optionCounts = [];
            foreach ($q->options as $opt) {
                if($q->type === 'checkbox') {
                    $matching = $qAnswers->filter(function($ans) use ($opt){
                        $vals = is_array($ans->value_json) ? $ans->value_json : []; return in_array($opt->value, $vals, true);
                    });
                } else {
                    $matching = $qAnswers->where('value_text',$opt->value);
                }
                $count = $matching->count();
                $respondents = $matching->map(function($ans){
                    $dir = $ans->submission->directory;
                    $phonesArr = [];
                    if($dir){
                        $raw = is_array($dir->phones) ? $dir->phones : ($dir->phones ? json_decode($dir->phones,true) : []);
                        if(is_array($raw)) { $phonesArr = $raw; }
                    }
                    return [
                        'submission_id' => $ans->submission->id ?? null,
                        'directory_id' => $ans->submission->directory_id,
                        'directory_name' => $dir?->name ?? '—',
                        'id_card_number' => $dir?->id_card_number ?? '—',
                        'phones' => $phonesArr,
                        'current_address' => $dir?->currentLocationString(),
                        'permanent_address' => $dir?->permanentLocationString(),
                    ];
                })
                ->unique(fn($r) => $r['submission_id'] ?: ($r['directory_id'].'-'.$r['id_card_number']))
                ->values()
                ->toArray();
                $optionCounts[] = [
                    'value' => $opt->value,
                    'label' => $opt->label ?? $opt->value,
                    'count' => $count,
                    'respondents' => $respondents,
                ];
            }
            $totalAnswered = $qAnswers->count();
            $stats[] = [
                'question_id' => $q->id,
                'question_text' => $q->question_text,
                'type' => $q->type,
                'total_answered' => $totalAnswered,
                'options' => $optionCounts,
            ];
        }
        $this->questionStats = $stats;

        // Load submissions with directory (list view)
        $subs = FormSubmission::with(['directory'])
            ->where('form_id',$this->form->id)
            ->latest()
            ->get();
        $this->totalSubmissions = $subs->count();
        $this->submissions = $subs->map(function($s){
            $dir = $s->directory;
            $phonesArr = [];
            if($dir){
                $raw = is_array($dir->phones) ? $dir->phones : ($dir->phones ? json_decode($dir->phones,true) : []);
                if(is_array($raw)) { $phonesArr = $raw; }
            }
            return [
                'id' => $s->id,
                'submitted_at' => $s->created_at,
                'directory_name' => $dir?->name,
                'id_card_number' => $dir?->id_card_number,
                'phones' => $phonesArr,
                'current_address' => $dir?->currentLocationString(),
                'permanent_address' => $dir?->permanentLocationString(),
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.forms.form-responses',[
            'form' => $this->form,
            'questionStats' => $this->questionStats,
            'submissions' => $this->submissions,
            'totalSubmissions' => $this->totalSubmissions,
        ])->layout('layouts.master');
    }
}
