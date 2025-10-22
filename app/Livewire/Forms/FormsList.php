<?php

namespace App\Livewire\Forms;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Form;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class FormsList extends Component
{
    use WithPagination,AuthorizesRequests;

    public $search = '';
    public $status = '';
    public $language = '';
    public $confirmingDeleteId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'language' => ['except' => ''],
        'page' => ['except' => 1],
    ];

    protected $listeners = ['form-saved' => '$refresh'];

    public function updatingSearch(){ $this->resetPage(); }
    public function updatingStatus(){ $this->resetPage(); }
    public function updatingLanguage(){ $this->resetPage(); }

    public function confirmDelete($id){ $this->confirmingDeleteId = $id; }
    public function cancelDelete(){ $this->confirmingDeleteId = null; }

    public function deleteForm()
    {
        if($this->confirmingDeleteId){
            Form::where('id',$this->confirmingDeleteId)->delete();
            $this->confirmingDeleteId = null;
            session()->flash('message','Form deleted.');
            $this->resetPage();
        }
    }

    public function duplicate($id)
    {
        $original = Form::with('sections.questions.options')->findOrFail($id);
        $new = $original->replicate(['slug','version']);
        $new->slug = $original->slug.'-copy-'.time();
        $new->status = 'draft';
        $new->version = 1;
        $new->save();
        foreach($original->sections as $section){
            $newSection = $section->replicate(['form_id']);
            $newSection->form_id = $new->id;
            $newSection->save();
            foreach($section->questions as $q){
                $newQ = $q->replicate(['form_id','form_section_id']);
                $newQ->form_id = $new->id;
                $newQ->form_section_id = $newSection->id;
                $newQ->save();
                foreach($q->options as $opt){
                    $newOpt = $opt->replicate(['form_question_id']);
                    $newOpt->form_question_id = $newQ->id;
                    $newOpt->save();
                }
            }
        }
        session()->flash('message','Form duplicated.');
        return redirect()->route('forms.edit', $new->id);
    }

    public function render()
    {
        $this->authorize('formslist-render');

        $query = Form::query();
        if($this->search){
            $query->where('title','like','%'.$this->search.'%');
        }
        if($this->status){
            $query->where('status',$this->status);
        }
        if($this->language){
            $query->where('language',$this->language);
        }
        $forms = $query->latest()->paginate(10);

        return view('livewire.forms.forms-list', [
            'forms' => $forms,
        ])->layout('layouts.master');
    }
}
