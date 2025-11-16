<?php

namespace App\Livewire\System;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\SubStatus;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SubStatusManagement extends Component
{
    use WithPagination, AuthorizesRequests;

    public $name='';
    public $description='';
    public $editId=null;
    public $editName='';
    public $editDescription='';
    public $search='';

    protected $queryString = [ 'search' => ['except'=>''] ];

    public function updatingSearch(){ $this->resetPage(); }

    public function resetForm(){ $this->reset(['name','description']); }

    public function create(): void
    {
        $this->authorize('sub-status-create');
        $this->validate([
            'name' => 'required|string|max:255|unique:sub_statuses,name',
            'description' => 'nullable|string|max:500',
        ]);
        SubStatus::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
        ]);
        $this->resetForm();
        $this->dispatch('swal', icon:'success', title:'Added', text:'Sub status created.');
    }

    public function edit($id): void
    {
        $this->authorize('sub-status-edit');
        $ss = SubStatus::find($id); if(!$ss) return;
        $this->editId = $ss->id; $this->editName = $ss->name; $this->editDescription = $ss->description;
    }

    public function update(): void
    {
        $this->authorize('sub-status-edit');
        if(!$this->editId) return; $ss = SubStatus::find($this->editId); if(!$ss) return;
        $this->validate([
            'editName' => ['required','string','max:255', Rule::unique('sub_statuses','name')->ignore($ss->id)],
            'editDescription' => 'nullable|string|max:500',
        ]);
        $ss->update([
            'name' => $this->editName,
            'description' => $this->editDescription ?: null,
        ]);
        $this->dispatch('swal', icon:'success', title:'Updated', text:'Sub status updated.');
        $this->cancelEdit();
    }

    public function toggle($id): void
    {
        $this->authorize('sub-status-toggle');
        $ss = SubStatus::find($id); if(!$ss) return;
        $ss->active = !$ss->active; $ss->save();
        $this->dispatch('swal', icon:'success', title:'Status Changed', text:'Status toggled.');
    }

    public function cancelEdit(): void
    { $this->reset(['editId','editName','editDescription']); }

    public function render()
    {
        $this->authorize('sub-status-render');
        $statuses = SubStatus::query()
            ->when($this->search, fn($q)=>$q->where('name','like','%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);
        return view('livewire.system.sub-status-management',[ 'statuses'=>$statuses ])
            ->layout('layouts.master');
    }
}
