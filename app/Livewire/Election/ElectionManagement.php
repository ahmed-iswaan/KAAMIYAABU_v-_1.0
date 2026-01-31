<?php

namespace App\Livewire\Election;

use App\Models\Election;
use App\Models\EventLog;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ElectionManagement extends Component
{
    use AuthorizesRequests;

    public bool $showModal = false;
    public string $mode = 'create'; // create|edit

    public ?string $editingId = null;
    public string $name = '';
    public ?string $startDate = null; // Y-m-d
    public ?string $endDate = null;   // Y-m-d
    public string $status = Election::STATUS_ACTIVE;

    public function openCreate(): void
    {
        $this->authorize('elections-manage-render');

        $this->resetErrorBag();
        $this->mode = 'create';
        $this->editingId = null;
        $this->name = '';
        $this->startDate = now()->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
        $this->status = Election::STATUS_ACTIVE;
        $this->showModal = true;
    }

    public function openEdit(string $id): void
    {
        $this->authorize('elections-manage-render');

        $election = Election::findOrFail($id);

        $this->resetErrorBag();
        $this->mode = 'edit';
        $this->editingId = (string) $election->id;
        $this->name = (string) $election->name;
        $this->startDate = optional($election->start_date)->format('Y-m-d');
        $this->endDate = optional($election->end_date)->format('Y-m-d');
        $this->status = (string) $election->status;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'status' => ['required', Rule::in([Election::STATUS_UPCOMING, Election::STATUS_ACTIVE, Election::STATUS_COMPLETED])],
        ];
    }

    public function save(): void
    {
        $this->authorize('elections-manage-save');

        $this->validate();

        DB::transaction(function () {
            if ($this->mode === 'edit' && $this->editingId) {
                $election = Election::findOrFail($this->editingId);
            } else {
                $election = new Election();
            }

            // Enforce only one active election
            if ($this->status === Election::STATUS_ACTIVE) {
                Election::query()->where('status', Election::STATUS_ACTIVE)->update(['status' => Election::STATUS_UPCOMING]);
            }

            $election->fill([
                'name' => $this->name,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'status' => $this->status,
            ]);
            $election->save();

            EventLog::create([
                'user_id' => Auth::id(),
                'event_tab' => 'Election',
                'event_entry_id' => $election->id,
                'event_type' => $this->mode === 'edit' ? 'Election Updated' : 'Election Created',
                'description' => $this->mode === 'edit' ? 'Election updated' : 'Election created',
                'event_data' => [
                    'election_id' => (string) $election->id,
                    'name' => (string) $election->name,
                    'start_date' => (string) $election->start_date,
                    'end_date' => (string) $election->end_date,
                    'status' => (string) $election->status,
                ],
                'ip_address' => request()->ip(),
            ]);

            // If we just deactivated the only active election, activate the newest one.
            if ($this->status !== Election::STATUS_ACTIVE) {
                $activeCount = Election::query()->where('status', Election::STATUS_ACTIVE)->count();
                if ($activeCount === 0) {
                    $fallback = Election::query()->orderByDesc('start_date')->first();
                    if ($fallback) {
                        $fallback->update(['status' => Election::STATUS_ACTIVE]);
                    }
                }
            }
        });

        $this->showModal = false;

        $this->dispatch('swal', [
            'icon' => 'success',
            'title' => 'Saved',
            'text' => 'Election saved.',
            'showConfirmButton' => false,
            'timer' => 1200,
        ]);
    }

    public function setActive(string $id): void
    {
        $this->authorize('elections-manage-toggle');

        DB::transaction(function () use ($id) {
            Election::query()->where('status', Election::STATUS_ACTIVE)->update(['status' => Election::STATUS_UPCOMING]);
            $election = Election::findOrFail($id);
            $election->update(['status' => Election::STATUS_ACTIVE]);

            EventLog::create([
                'user_id' => Auth::id(),
                'event_tab' => 'Election',
                'event_entry_id' => $election->id,
                'event_type' => 'Election Activated',
                'description' => 'Election set to Active',
                'event_data' => ['election_id' => (string) $election->id],
                'ip_address' => request()->ip(),
            ]);
        });
    }

    public function setInactive(string $id): void
    {
        $this->authorize('elections-manage-toggle');

        DB::transaction(function () use ($id) {
            $election = Election::findOrFail($id);
            $election->update(['status' => Election::STATUS_UPCOMING]);

            // Ensure at least one active election exists
            $activeCount = Election::query()->where('status', Election::STATUS_ACTIVE)->count();
            if ($activeCount === 0) {
                $fallback = Election::query()->where('id', '!=', $id)->orderByDesc('start_date')->first();
                if ($fallback) {
                    $fallback->update(['status' => Election::STATUS_ACTIVE]);
                }
            }

            EventLog::create([
                'user_id' => Auth::id(),
                'event_tab' => 'Election',
                'event_entry_id' => $election->id,
                'event_type' => 'Election Deactivated',
                'description' => 'Election set to Inactive (Upcoming)',
                'event_data' => ['election_id' => (string) $election->id],
                'ip_address' => request()->ip(),
            ]);
        });
    }

    public function render()
    {
        $this->authorize('elections-manage-render');

        $elections = Election::query()
            ->orderByRaw("CASE WHEN status = 'Active' THEN 0 ELSE 1 END")
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'start_date', 'end_date', 'status']);

        return view('livewire.election.election-management', [
            'elections' => $elections,
        ])->layout('layouts.master');
    }
}
