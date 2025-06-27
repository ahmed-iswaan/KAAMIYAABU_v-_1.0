<?php

namespace App\Livewire\Waste;

use App\Models\WasteCollectionTask;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class TaskList extends Component
{
    use WithPagination;

    public $search;
    public $statusFilter = 'pending';
    public $scheduledDate;

    protected $queryString = ['search', 'statusFilter', 'scheduledDate'];

    public function mount()
    {
        $this->scheduledDate = Carbon::today()->toDateString();
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingScheduledDate() { $this->resetPage(); }

    public function render()
    {
        $dates = collect(range(0, 30))
            ->map(fn ($i) => Carbon::today()->subDays($i))
            ->map(fn ($date, $index) => [
                'label' => $date->format('D'),
                'day' => $date->format('d'),
                'value' => $date->toDateString(),
                'id' => "kt_schedule_day_{$index}",
            ])
            ->values();

        $tasks = WasteCollectionTask::query()
            ->when($this->search, fn ($q) =>
                $q->whereHas('property', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
                  ->orWhereHas('directory', fn ($q) => $q->where('name', 'like', '%' . $this->search . '%'))
            )
            ->when($this->statusFilter, fn ($q) =>
                $q->where('status', $this->statusFilter)
            )
            ->when($this->scheduledDate, fn ($q) =>
                $q->whereDate('scheduled_at', Carbon::parse($this->scheduledDate))
            )
            ->with(['property', 'directory', 'vehicle', 'driver'])
            ->latest()
            ->paginate(10);

        return view('livewire.waste.task-list', [
            'tasks' => $tasks,
            'dates' => $dates,
            'groupedTasks' => $tasks->groupBy(fn ($task) => optional($task->scheduled_at)->format('H:i')),
        ])->layout('layouts.master');
    }
}