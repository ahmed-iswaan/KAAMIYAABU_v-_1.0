<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Models\FormSubmissionAnswer;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormResponsesExportController extends Controller
{
    public function exportOptionRespondents(Request $request, Form $form): StreamedResponse
    {
        $this->authorize('formslist-render');

        $questionIds = $form->questions()->whereIn('type',['radio','select','checkbox'])->pluck('id');
        $answers = FormSubmissionAnswer::with(['submission.directory'])
            ->whereIn('form_question_id',$questionIds)
            ->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="form_option_respondents_'.$form->id.'.csv"',
        ];

        $callback = function() use ($answers, $form) {
            $out = fopen('php://output','w');
            fputcsv($out,[
                'Option Value','Directory Name','ID Card','Phones','Current Address','Permanent Address','Answered At'
            ]);

            foreach ($answers as $ans) {
                $q = $ans->question; if(!$q || !in_array($q->type,['radio','select','checkbox'])) continue;
                $dir = $ans->submission?->directory;
                $phonesRaw = [];
                if($dir){
                    $raw = is_array($dir->phones) ? $dir->phones : ($dir->phones ? json_decode($dir->phones,true) : []);
                    if(is_array($raw)) $phonesRaw = $raw;
                }
                $optionValue = $q->type==='checkbox' ? json_encode($ans->value_json, JSON_UNESCAPED_UNICODE) : $ans->value_text;
                fputcsv($out,[
                    $optionValue,
                    $dir?->name,
                    $dir?->id_card_number,
                    implode(';',$phonesRaw),
                    $dir?->currentLocationString(),
                    $dir?->permanentLocationString(),
                    $ans->created_at,
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback,200,$headers);
    }

    public function exportSingleOption(Form $form, \App\Models\FormQuestion $question, string $optionValue)
    {
        $this->authorize('formslist-render');
        abort_unless($question->form_id === $form->id, 404);

        $answersQ = \App\Models\FormSubmissionAnswer::with(['submission.directory'])
            ->where('form_question_id',$question->id);
        if(in_array($question->type,['radio','select'])){
            $answersQ->where('value_text',$optionValue);
        } elseif($question->type==='checkbox') {
            $answersQ->whereJsonContains('value_json',$optionValue); // single checkbox option
        } else {
            abort(400,'Unsupported question type');
        }
        $answers = $answersQ->get();

        $filename = 'form_option_'.$form->id.'_'.$question->id.'_'.preg_replace('/[^A-Za-z0-9_-]/','_', $optionValue).'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function() use ($answers,$form,$question,$optionValue){
            $out = fopen('php://output','w');
            fputcsv($out,['Option Value','Directory Name','ID Card','Phones','Current Address','Permanent Address','Answered At']);
            foreach($answers as $ans){
                $dir = $ans->submission?->directory; $phonesRaw=[]; if($dir){ $raw = is_array($dir->phones)? $dir->phones : ($dir->phones ? json_decode($dir->phones,true):[]); if(is_array($raw)) $phonesRaw=$raw; }
                fputcsv($out,[
                    $optionValue,
                    $dir?->name,
                    $dir?->id_card_number,
                    implode(';',$phonesRaw),
                    $dir?->currentLocationString(),
                    $dir?->permanentLocationString(),
                    $ans->created_at,
                ]);
            }
            fclose($out);
        },200,$headers);
    }
}
