<?php

namespace App\Livewire\Forms;

use Livewire\Component;
use App\Models\{Form,FormSection,FormQuestion,FormQuestionOption};
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FormBuilder extends Component
{
    public $formId = null;
    public $language = 'en'; // 'en' or 'dv'
    public $title = '';
    public $description = '';
    public $status = 'draft';

    public $sections = []; // array of arrays
    public $questions = []; // keyed by temporary uid
    public $deletedQuestionIds = []; // track deletions for audit if needed
    public $deletedSectionIds = []; // track deleted persisted sections

    public $activeTab = 'structure';

    protected $rules = [
        'language' => 'required|in:en,dv',
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'status' => 'in:draft,published,archived'
    ];

    public function mount($form = null)
    {
        if($form){
            $this->loadForm($form);
        } else {
            $this->addSection();
        }
    }

    public function loadForm($formId)
    {
        $f = Form::with('sections.questions.options')->findOrFail($formId);
        $this->formId = $f->id;
        $this->language = $f->language;
        $this->title = $f->title;
        $this->description = $f->description;
        $this->status = $f->status;
        $this->sections = [];
        $this->questions = [];
        foreach($f->sections as $section){
            $this->sections[] = [
                'id' => $section->id,
                'title' => $section->title,
                'description' => $section->description,
                'position' => $section->position,
            ];
            $currentIndex = count($this->sections) - 1; // map index for existing questions
            foreach($section->questions as $q){
                $uid = 'q_'.$q->id;
                $this->questions[$uid] = [
                    'id' => $q->id,
                    'section_id' => $section->id,
                    'section_index' => $currentIndex, // ensure builder compatibility
                    'type' => $q->type,
                    'code' => $q->code,
                    'question_text' => $q->question_text,
                    'help_text' => $q->help_text,
                    'is_required' => $q->is_required,
                    'options' => $q->options->map(fn($o)=>[
                        'id'=>$o->id,
                        'value'=>$o->value,
                        'label'=>$o->label,
                        'position'=>$o->position,
                    ])->toArray(),
                ];
            }
        }
    }

    public function addSection()
    {
        $this->sections[] = [
            'id' => null,
            'title' => 'New Section',
            'description' => null,
            'position' => count($this->sections)
        ];
    }

    public function removeSection($index)
    {
        if(!isset($this->sections[$index])) return;
        $removed = $this->sections[$index];
        $removedSectionId = $removed['id'] ?? null;

        // Remove questions belonging to this section
        foreach($this->questions as $uid=>$q){
            $belongs = false;
            if(isset($q['section_index']) && $q['section_index'] === $index) $belongs = true;
            if(!$belongs && isset($q['section_id']) && $removedSectionId && $q['section_id'] === $removedSectionId) $belongs = true;
            if($belongs){
                if(!empty($q['id'])){
                    // Delete immediately to keep DB in sync
                    FormQuestionOption::where('form_question_id',$q['id'])->delete();
                    FormQuestion::where('id',$q['id'])->delete();
                    $this->deletedQuestionIds[] = $q['id'];
                }
                unset($this->questions[$uid]);
            }
        }

        // Track section id for deletion if persisted
        if($removedSectionId){
            $this->deletedSectionIds[] = $removedSectionId;
            // Delete immediately (optional early delete) - safe inside try/catch
            try {
                // Delete related questions/options if any remain
                $questionIds = FormQuestion::where('form_section_id',$removedSectionId)->pluck('id');
                if($questionIds->count()){
                    FormQuestionOption::whereIn('form_question_id',$questionIds)->delete();
                    FormQuestion::whereIn('id',$questionIds)->delete();
                }
                FormSection::where('id',$removedSectionId)->delete();
            } catch(\Throwable $e) {
                // Silent fail; will be caught in save diff logic
            }
        }

        // Remove section from array and reindex
        unset($this->sections[$index]);
        $this->sections = array_values($this->sections);

        // Re-map question section_index values > removed index
        foreach($this->questions as $uid=>&$q){
            if(isset($q['section_index']) && $q['section_index'] > $index){
                $q['section_index'] = $q['section_index'] - 1;
            }
        }
    }

    public function addQuestion($sectionIndex, $type = 'short_text')
    {
        $uid = Str::uuid()->toString();
        $this->questions[$uid] = [
            'id' => null,
            'section_index' => $sectionIndex,
            'section_id' => $this->sections[$sectionIndex]['id'] ?? null,
            'type' => $type,
            'code' => null,
            'question_text' => 'New question',
            'help_text' => null,
            'is_required' => false,
            'options' => in_array($type,['select','multiselect','radio','checkbox']) ? [
                ['id'=>null,'value'=>'opt_1','label'=>'Option 1','position'=>0],
                ['id'=>null,'value'=>'opt_2','label'=>'Option 2','position'=>1],
            ] : []
        ];
    }

    public function addOption($questionUid)
    {
        $q =& $this->questions[$questionUid];
        $q['options'][] = [
            'id'=>null,
            'value'=>'opt_'.(count($q['options'])+1),
            'label'=>'Option '.(count($q['options'])+1),
            'position'=>count($q['options'])
        ];
    }

    public function removeOption($questionUid,$optIndex)
    {
        $q =& $this->questions[$questionUid];
        unset($q['options'][$optIndex]);
        $q['options'] = array_values($q['options']);
    }

    public function toggleRequired($questionUid)
    {
        $this->questions[$questionUid]['is_required'] = ! $this->questions[$questionUid]['is_required'];
    }

    public function deleteQuestion($uid)
    {
        if(!isset($this->questions[$uid])) { return; }
        $q = $this->questions[$uid];
        if(!empty($q['id'])) {
            // Delete existing question & its options immediately
            FormQuestionOption::where('form_question_id', $q['id'])->delete();
            FormQuestion::where('id', $q['id'])->delete();
            $this->deletedQuestionIds[] = $q['id'];
        }
        unset($this->questions[$uid]);
    }

    public function save()
    {
        $this->validate();

        DB::transaction(function(){
            if($this->formId){
                $form = Form::findOrFail($this->formId);
                $form->update([
                    'language'=>$this->language,
                    'title'=>$this->title,
                    'description'=>$this->description,
                    'status'=>$this->status,
                ]);
            } else {
                $form = Form::create([
                    'slug'=>Str::slug($this->title).'-'.time(),
                    'language'=>$this->language,
                    'title'=>$this->title,
                    'description'=>$this->description,
                    'status'=>$this->status,
                    'created_by'=>auth()->id(),
                ]);
                $this->formId = $form->id;
            }

            // Fetch existing section ids before sync
            $existingSectionIds = FormSection::where('form_id',$form->id)->pluck('id')->toArray();

            // sync sections
            $sectionIdMap = [];
            foreach($this->sections as $idx=>$sectionData){
                if($sectionData['id']){
                    $section = FormSection::find($sectionData['id']);
                    if($section){
                        $section->update([
                            'title'=>$sectionData['title'],
                            'description'=>$sectionData['description'],
                            'position'=>$idx,
                        ]);
                    }
                } else {
                    $section = FormSection::create([
                        'form_id'=>$form->id,
                        'title'=>$sectionData['title'],
                        'description'=>$sectionData['description'],
                        'position'=>$idx,
                    ]);
                    $this->sections[$idx]['id'] = $section->id;
                }
                $sectionIdMap[$idx] = $this->sections[$idx]['id'];
            }

            $currentSectionIds = array_values($sectionIdMap);
            $toDelete = array_diff($existingSectionIds, $currentSectionIds);

            if(count($toDelete)){
                $questionIds = FormQuestion::whereIn('form_section_id',$toDelete)->pluck('id');
                if($questionIds->count()){
                    FormQuestionOption::whereIn('form_question_id',$questionIds)->delete();
                    FormQuestion::whereIn('id',$questionIds)->delete();
                }
                FormSection::whereIn('id',$toDelete)->delete();
            }

            // sync questions
            foreach($this->questions as $uid=>$qData){
                // Resolve section index if missing
                $sectionIndex = $qData['section_index'] ?? null;
                if($sectionIndex === null && isset($qData['section_id'])){
                    foreach($this->sections as $idx=>$sec){
                        if(($sec['id'] ?? null) === $qData['section_id']){
                            $sectionIndex = $idx;
                            $this->questions[$uid]['section_index'] = $idx; // cache for runtime
                            break;
                        }
                    }
                }
                if($sectionIndex === null){ $sectionIndex = 0; }
                $finalSectionId = $sectionIdMap[$sectionIndex] ?? ($qData['section_id'] ?? null);
                if(!$finalSectionId){ continue; }

                if(!empty($qData['id'])){
                    $question = FormQuestion::find($qData['id']);
                    if($question){
                        $question->update([
                            'form_id'=>$form->id,
                            'form_section_id'=>$finalSectionId,
                            'type'=>$qData['type'],
                            'code'=>$qData['code'],
                            'question_text'=>$qData['question_text'],
                            'help_text'=>$qData['help_text'],
                            'is_required'=>$qData['is_required'],
                            'position'=>0, // TODO ordering
                        ]);
                    }
                } else {
                    $question = FormQuestion::create([
                        'form_id'=>$form->id,
                        'form_section_id'=>$finalSectionId,
                        'type'=>$qData['type'],
                        'code'=>$qData['code'],
                        'question_text'=>$qData['question_text'],
                        'help_text'=>$qData['help_text'],
                        'is_required'=>$qData['is_required'],
                        'position'=>0,
                    ]);
                    $this->questions[$uid]['id'] = $question->id;
                }

                if(in_array($qData['type'],['select','multiselect','radio','checkbox'])){
                    foreach($qData['options'] as $optIdx=>$opt){
                        if(isset($opt['id']) && $opt['id']){
                            $option = FormQuestionOption::find($opt['id']);
                            if($option){
                                $option->update([
                                    'value'=>$opt['value'],
                                    'label'=>$opt['label'],
                                    'position'=>$optIdx,
                                ]);
                            }
                        } else {
                            if(!isset($this->questions[$uid]['id'])) continue;
                            $new = FormQuestionOption::create([
                                'form_question_id'=>$this->questions[$uid]['id'],
                                'value'=>$opt['value'],
                                'label'=>$opt['label'],
                                'position'=>$optIdx,
                            ]);
                            $this->questions[$uid]['options'][$optIdx]['id'] = $new->id;
                        }
                    }
                }
            }
        });

        $this->dispatch('form-saved');
        $this->dispatch('swal', type: 'success', title: 'Saved', text: 'Form saved successfully.');
        session()->flash('message','Form saved successfully.');
    }

    public function render()
    {
        return view('livewire.forms.form-builder')->layout('layouts.master');
    }
}
