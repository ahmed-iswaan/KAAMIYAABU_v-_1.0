<?php

namespace App\Livewire\System;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\RequestType;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class RequestTypesManagement extends Component
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
        $this->authorize('request-types-create');

        $this->validate([
            'name' => 'required|string|max:255|unique:request_types,name',
            'description' => 'nullable|string|max:500',
        ]);
        RequestType::create([
            'name' => $this->name,
            'description' => $this->description ?: null,
        ]);
        $this->resetForm();
        $this->dispatch('swal', icon:'success', title:'Added', text:'Request type created.');
    }

    public function edit($id): void
    {
        $this->authorize('request-types-edit');

        $rt = RequestType::find($id);
        if(!$rt){ return; }
        $this->editId = $rt->id;
        $this->editName = $rt->name;
        $this->editDescription = $rt->description;
    }

    public function update(): void
    {
        $this->authorize('request-types-edit');

        if(!$this->editId) return;
        $rt = RequestType::find($this->editId);
        if(!$rt) return;
        $this->validate([
            'editName' => ['required','string','max:255', Rule::unique('request_types','name')->ignore($rt->id)],
            'editDescription' => 'nullable|string|max:500',
        ]);
        $rt->update([
            'name' => $this->editName,
            'description' => $this->editDescription ?: null,
        ]);
        $this->dispatch('swal', icon:'success', title:'Updated', text:'Request type updated.');
        $this->cancelEdit();
    }

    public function toggle($id): void
    {
        $this->authorize('request-types-toggle');

        $rt = RequestType::find($id);
        if(!$rt) return;
        $rt->active = !$rt->active;
        $rt->save();
        $this->dispatch('swal', icon:'success', title:'Status Changed', text:'Status toggled.');
    }

    public function cancelEdit(): void
    {
        $this->reset(['editId','editName','editDescription']);
    }

    public function render()
    {
        $this->authorize('request-types-render');

        $types = RequestType::query()
            ->when($this->search, fn($q)=>$q->where('name','like','%'.$this->search.'%'))
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.system.request-types-management', [
            'types' => $types,
        ])->layout('layouts.master');
    }
}
