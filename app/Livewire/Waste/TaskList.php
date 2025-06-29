<?php

namespace App\Livewire\Waste;

use App\Models\WasteCollectionTask;
use App\Models\WasteType;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Http\Request;

class TaskList extends Component
{
    use WithPagination;

    public $search;
    public $statusFilter = 'pending';
    public $scheduledDate;

    public $selectedTask;
    public $newStatus;
    public $modalError = null;

    public $completed_latitude;
    public $completed_longitude;

    public $wasteInputs = []; // For dynamic input
    public $wasteTypes = [];  // For all available types



    protected $queryString = ['search', 'statusFilter', 'scheduledDate'];

    protected $listeners = [
        'set-device-location' => 'setDeviceLocation',
    ];

    public function setDeviceLocation($lat, $lng)
    {
        $this->completed_latitude = $lat;
        $this->completed_longitude = $lng;
    }


    public function mount(Request $request)
    {
        $this->scheduledDate = Carbon::today()->toDateString(); // set this first

        if ($request->has('register_number')) {
            $this->openModal($request->get('register_number'));
        }
    }

    public function updatingSearch() { $this->resetPage(); }
    public function updatingStatusFilter() { $this->resetPage(); }
    public function updatingScheduledDate() { $this->resetPage(); }

    

    

    public function openModal($registerNumber)
    {
        $this->wasteTypes = WasteType::orderBy('index')->get();

        $task = WasteCollectionTask::whereHas('register', function ($q) use ($registerNumber) {
            $q->where('register_number', $registerNumber);
        })
        ->whereDate('scheduled_at', $this->scheduledDate)
        ->where('status', $this->statusFilter)
        ->first();

        if (!$task) {
            $this->modalError = 'No matching task found for the selected date and status.';
            $this->selectedTask = null;
            $this->wasteInputs = [];
        } else {
            $this->selectedTask = $task;
            $this->newStatus = $task->status;
            $this->modalError = null;

            $existingData = collect($task->waste_data ?? []);

            $this->wasteInputs = $this->wasteTypes->map(function ($type) use ($existingData) {
                $match = $existingData->firstWhere('waste_type_id', $type->id);
                return [
                    'waste_type_id' => $type->id,
                    'amount' => $match['amount'] ?? null,
                ];
            })->toArray();
        }

        $this->dispatch('show-change-status-modal');
    }


    public function updateStatus()
    {
        if (!$this->selectedTask || !$this->newStatus) return;

        // ✅ Check if location is available
        if (empty($this->completed_latitude) || empty($this->completed_longitude)) {
            $this->dispatch('swal', [
                'title' => 'Location Required',
                'text' => '📍 Please allow location access before submitting.',
                'icon' => 'error',
                'buttonsStyling' => false,
                'confirmButtonText' => 'Ok',
                'confirmButton' => 'btn btn-danger',
            ]);
            return;
        }

        // Step 1: Roll back previous waste_data
        if ($this->selectedTask->waste_data) {
            foreach ($this->selectedTask->waste_data as $old) {
                if (!empty($old['waste_type_id']) && !empty($old['amount'])) {
                    WasteType::where('id', $old['waste_type_id'])->decrement('total_collection', $old['amount']);
                }
            }
        }

        // Step 2: Apply new waste data
        $cleanedWasteInputs = array_filter($this->wasteInputs, fn($item) => !empty($item['amount']));
        foreach ($cleanedWasteInputs as $item) {
            if (!empty($item['waste_type_id']) && !empty($item['amount'])) {
                WasteType::where('id', $item['waste_type_id'])->increment('total_collection', $item['amount']);
            }
        }

        // Step 3: Update task details
        $this->selectedTask->status = $this->newStatus;
        $this->selectedTask->waste_data = $cleanedWasteInputs;
        $this->selectedTask->total_collected = collect($cleanedWasteInputs)->pluck('amount')->sum();
        $this->selectedTask->completed_latitude = $this->completed_latitude;
        $this->selectedTask->completed_longitude = $this->completed_longitude;
        $this->selectedTask->completed_at = now();
        $this->selectedTask->driver_id = auth()->id();
        $this->selectedTask->save();

        session()->flash('message', 'Status and waste data updated successfully.');
        $this->dispatch('hide-change-status-modal');

        $this->dispatch('swal', [
            'title' => 'Success',
            'text' => 'Waste Collected.',
            'icon' => 'success',
            'buttonsStyling' => false,
            'confirmButtonText' => 'Ok!',
            'confirmButton' => 'btn btn-primary',
        ]);
    }


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