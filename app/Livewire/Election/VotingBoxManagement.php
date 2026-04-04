<?php

namespace App\Livewire\Election;

use App\Models\SubConsite;
use App\Models\VotingBox;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class VotingBoxManagement extends Component
{
    use WithPagination, AuthorizesRequests;

    public string $search = '';
    public int $perPage = 25;

    public ?string $editingId = null;
    public string $name = '';
    public ?string $sub_consite_id = null;

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sub_consite_id' => ['nullable', 'uuid', 'exists:sub_consites,id'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('voting-boxes-render');

        $this->resetForm();
        $this->editingId = 'new';
    }

    public function edit(string $id): void
    {
        $this->authorize('voting-boxes-render');

        $box = VotingBox::query()->findOrFail($id);
        $this->editingId = $box->id;
        $this->name = (string) $box->name;
        $this->sub_consite_id = $box->sub_consite_id;
    }

    public function cancel(): void
    {
        $this->resetForm();
        $this->editingId = null;
    }

    public function save(): void
    {
        $this->authorize('voting-boxes-render');

        $data = $this->validate();

        if ($this->editingId === 'new') {
            VotingBox::query()->create($data);
        } else {
            $box = VotingBox::query()->findOrFail($this->editingId);
            $box->fill($data);
            $box->save();
        }

        $this->cancel();
    }

    public function getBoxesProperty()
    {
        $this->authorize('voting-boxes-render');

        return VotingBox::query()
            ->when($this->search !== '', function ($q) {
                $term = '%'.$this->search.'%';
                $q->where('name', 'like', $term);
            })
            ->with(['subConsite:id,code,name'])
            ->withCount('directories')
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function getSubConsitesProperty()
    {
        return SubConsite::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->sub_consite_id = null;
    }

    public function render()
    {
        return view('livewire.election.voting-box-management', [
            'boxes' => $this->boxes,
        ]);
    }
}
