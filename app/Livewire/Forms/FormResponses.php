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
    public $questionStats = []; // per question option counts
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
        $answers = FormSubmissionAnswer::whereIn('form_question_id',$questionIds)->get()->groupBy('form_question_id');

        $stats = [];
        foreach ($this->form->questions as $q) {
            if(! in_array($q->type, ['radio','checkbox','select'])) { continue; }
            $qAnswers = $answers->get($q->id, collect());
            $optionCounts = [];
            foreach ($q->options as $opt) {
                if($q->type === 'checkbox') {
                    $count = $qAnswers->filter(function($ans) use ($opt){
                        $vals = is_array($ans->value_json) ? $ans->value_json : []; return in_array($opt->value, $vals, true);
                    })->count();
                } else { // radio/select
                    $count = $qAnswers->where('value_text',$opt->value)->count();
                }
                $optionCounts[] = [
                    'value' => $opt->value,
                    'label' => $opt->label ?? $opt->value,
                    'count' => $count,
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

        // Load submissions with directory
        $subs = FormSubmission::with(['directory'])
            ->where('form_id',$this->form->id)
            ->latest()
            ->get();
        $this->totalSubmissions = $subs->count();
        $this->submissions = $subs->map(function($s){
            return [
                'id' => $s->id,
                'submitted_at' => $s->created_at,
                'directory_name' => $s->directory?->name,
                'id_card_number' => $s->directory?->id_card_number,
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
