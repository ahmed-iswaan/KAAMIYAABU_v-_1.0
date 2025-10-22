<?php

namespace App\Livewire\Property;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Property;
use App\Models\Island;
use App\Models\Wards;
use App\Models\PropertyTypes;
use App\Models\EventLog;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use App\Models\PendingTelegramNotification;
use League\Csv\Writer;
use SplTempFileObject;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PropertyManagement extends Component
{
    use WithPagination, AuthorizesRequests;

    public $search = '';
    public $perPage = 10;
    public $pageTitle = 'Property Management';

    // Add Form Variables
    public $name, $property_type_id, $register_number, $number, $square_feet;
    public $island_id, $street_address, $ward_id, $latitude, $longitude;

    // Edit Form Variables
    public $editingProperty = false, $editId;
    public $editName, $editStreetAddress, $editPropertyTypeId, $editRegisterNumber;
    public $editNumber, $editSquareFeet, $editIslandId, $editWardId, $editLatitude, $editLongitude;

    public $types = [], $islands = [], $wards = [];

    protected $listeners = [
        'setLatitude' => 'setLatitude',
        'setLongitude' => 'setLongitude',
        'showAddModal' => 'openAddModal',
        'showEditModal' => 'editProperty'
    ];

    protected $rules = [];

    public function mount()
    {
        $this->loadLookups();
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->dispatch('TableUpdated'); 
    }

    

    public function loadLookups()
    {
        $this->types = PropertyTypes::orderBy('name')->get();
        $this->islands = Island::with('atoll')->orderBy('name')->get();
        $this->wards = collect();

        $mulah = $this->islands
            ->filter(fn($isl) => optional($isl->atoll)->code === 'M')
            ->filter(fn($isl) => Str::contains($isl->name, 'Mulah'))
            ->first();
        $this->island_id = $mulah->id ?? null;
    }

    public function updatedIslandId($value)
    {
        $this->wards = Wards::where('island_id', $value)->orderBy('name')->get();
        $this->ward_id = null;
    }

     public function exportProperties()
    {
        $properties = Property::with(['island', 'ward', 'propertyType'])->get();

        return $this->exportToCSV($properties);
    }

    public function exportToCSV($properties)
    {
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne([
            'Name', 'Register No', 'Number', 'Sqft', 'Island', 'Ward', 'Street', 'Lat', 'Lng', 'Type'
        ]);

        foreach ($properties as $property) {
            $csv->insertOne([
                $property->name,
                $property->register_number,
                $property->number,
                $property->square_feet,
                $property->island->name ?? '-',
                $property->ward->name ?? '-',
                $property->street_address,
                $property->latitude,
                $property->longitude,
                $property->propertyType->name ?? '-',
            ]);
        }

        $csvContent = (string) $csv;
        $tempFile = tempnam(sys_get_temp_dir(), 'properties_');
        file_put_contents($tempFile, $csvContent);

        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Property',
            'event_entry_id' => auth()->id(),
            'event_type' => 'Export Property List',
            'description' => 'Property list exported successfully.',
            'event_data' => [
                'export_completed_at' => now(),
                'number_of_properties' => $properties->count(),
                'export_file_size' => filesize($tempFile),
            ],
            'ip_address' => request()->ip(),
        ]);

        $this->dispatch('swal', [
            'title' => 'Exported',
            'text' => 'Property list exported successfully.',
            'icon' => 'success',
            'confirmButtonText' => 'OK',
            'confirmButton' => 'btn btn-primary'
        ]);

        $this->dispatch('closeModal');

        return response()->download($tempFile, 'PropertiesList.csv')->deleteFileAfterSend(true);
    }

    public function updatedEditIslandId($value)
    {
        $this->wards = Wards::where('island_id', $value)->orderBy('name')->get();
        $this->editWardId = null;
    }

    public function setLatitude($lat) { $this->latitude = $lat; }
    public function setLongitude($lng) { $this->longitude = $lng; }

    public function openAddModal()
    {
        $this->resetForm();
        $this->loadLookups();
        $this->editingProperty = false;
        $this->dispatch('showAddPropertyModal');
    }

    public function resetForm()
    {
        $this->reset([
            'name', 'property_type_id', 'register_number', 'number', 'square_feet',
            'island_id', 'ward_id', 'street_address', 'latitude', 'longitude',
            'editId', 'editName', 'editPropertyTypeId', 'editRegisterNumber', 'editNumber',
            'editSquareFeet', 'editIslandId', 'editWardId', 'editStreetAddress', 'editLatitude', 'editLongitude'
        ]);
    }

    public function createProperty()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'street_address' => 'required|string|max:255',
            'property_type_id' => 'required',
            'register_number' => 'required|string|max:100|unique:properties,register_number',
            'number' => 'required|string|max:100|unique:properties,number',
            'square_feet' => 'required|numeric|min:0',
            'island_id' => 'required|exists:islands,id',
            'ward_id' => 'nullable|exists:wards,id',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        $property = Property::create($this->only([
            'name', 'street_address', 'property_type_id', 'register_number', 'number',
            'square_feet', 'island_id', 'ward_id', 'latitude', 'longitude'
        ]));

        PendingTelegramNotification::create([
            'chat_id' => env('TELEGRAM_GROUP_PROPERTY'),
            'message_thread_id' => env('TELEGRAM_TOPIC_PROPERTY'),
            'message' => $this->buildTelegramMessage('Created', $property),
        ]);

        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Property',
            'event_entry_id' => $property->id,
            'event_type' => 'Property Created',
            'description' => 'A new property was added.',
            'event_data' => $property->toArray(),
            'ip_address' => request()->ip(),
        ]);

        session()->flash('success', 'Property added successfully.');
        $this->dispatch('swal', ['title' => 'Property Created', 'text' => 'Saved.', 'icon' => 'success','confirmButtonText' => 'Ok!','confirmButton'=> 'btn btn-primary']);
        $this->dispatch('closeAddPropertyModal');
    }

    public function editProperty($id)
    {
        $this->resetForm();
        $this->editingProperty = true;

        $property = Property::findOrFail($id);

        $this->editId = $property->id;
        $this->editName = $property->name;
        $this->editStreetAddress = $property->street_address;
        $this->editPropertyTypeId = $property->property_type_id;
        $this->editRegisterNumber = $property->register_number;
        $this->editNumber = $property->number;
        $this->editSquareFeet = $property->square_feet;
        $this->editIslandId = $property->island_id;
        $this->editWardId = $property->ward_id;
        $this->editLatitude = $property->latitude;
        $this->editLongitude = $property->longitude;

        $this->updatedEditIslandId($this->editIslandId);
        $this->dispatch('showEditPropertyModal');
    }

    public function updateProperty()
    {
        $property = Property::findOrFail($this->editId);

        $validated = $this->validate([
            'editName' => 'required|string|max:255',
            'editStreetAddress' => 'required|string|max:255',
            'editPropertyTypeId' => 'required|exists:property_types,id',
            'editRegisterNumber' => ['required', 'string', 'max:100', Rule::unique('properties', 'register_number')->ignore($property->id)],
            'editNumber' => ['required', 'string', 'max:100', Rule::unique('properties', 'number')->ignore($property->id)],
            'editSquareFeet' => 'required|numeric|min:0',
            'editIslandId' => 'required|exists:islands,id',
            'editWardId' => 'nullable|exists:wards,id',
            'editLatitude' => 'required|numeric',
            'editLongitude' => 'required|numeric',
        ], [], [
            'editName' => 'name',
            'editStreetAddress' => 'street address',
            'editPropertyTypeId' => 'property type',
            'editRegisterNumber' => 'registration number',
            'editNumber' => 'number',
            'editSquareFeet' => 'square feet',
            'editIslandId' => 'island',
            'editWardId' => 'ward',
            'editLatitude' => 'latitude',
            'editLongitude' => 'longitude',
        ]);

        $property->update([
            'name' => $this->editName,
            'street_address' => $this->editStreetAddress,
            'property_type_id' => $this->editPropertyTypeId,
            'register_number' => $this->editRegisterNumber,
            'number' => $this->editNumber,
            'square_feet' => $this->editSquareFeet,
            'island_id' => $this->editIslandId,
            'ward_id' => $this->editWardId,
            'latitude' => $this->editLatitude,
            'longitude' => $this->editLongitude,
        ]);

        PendingTelegramNotification::create([
            'chat_id' => env('TELEGRAM_GROUP_PROPERTY'),
            'message_thread_id' => env('TELEGRAM_TOPIC_PROPERTY'),
            'message' => $this->buildTelegramMessage('Updated', $property),
        ]);

        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Property',
            'event_entry_id' => $property->id,
            'event_type' => 'Property Updated',
            'description' => 'Property updated.',
            'event_data' => $property->toArray(),
            'ip_address' => request()->ip(),
        ]);

        session()->flash('success', 'Updated successfully.');
        $this->dispatch('swal', ['title' => 'Updated', 'text' => 'Changes saved.', 'icon' => 'success','confirmButtonText' => 'Ok!','confirmButton'=> 'btn btn-primary']);
        $this->dispatch('closeEditPropertyModal');
    }

    protected function buildTelegramMessage($event, $property)
    {
        $envLabel = app()->environment('production') ? '🟢 Production' : '🧪 Development';

        return "<b>🏠 Property {$event}</b>\n" .
               "<i>{$envLabel} Environment</i>\n" .
               "──────────────────────────────\n" .
               "<b>🏷️ Name:</b> {$property->name}\n" .
               "<b>📍 Address:</b> {$property->street_address}\n" .
               "<b>🧾 Reg No:</b> {$property->register_number}\n" .
               "<b>🔢 Number:</b> {$property->number}\n" .
               "<b>📐 Sq Ft:</b> {$property->square_feet}\n" .
               "<b>🗺️ Island:</b> " . optional($property->island)->name . "\n" .
               "<b>🏘️ Ward:</b> " . optional($property->ward)->name . "\n" .
               "<b>📌 Coords:</b> {$property->latitude}, {$property->longitude}\n" .
               "──────────────────────────────\n" .
               "<b>👤 By:</b> " . auth()->user()->name . "\n" .
               "<b>🕒 At:</b> " . now()->format('d M Y H:i');
    }

    public function render()
    {
        $this->authorize('property-render');

        $properties = Property::query()
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('register_number', 'like', "%{$this->search}%")
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.property.property-management', [
            'properties' => $properties,
            'pageTitle' => $this->pageTitle,
        ])->layout('layouts.master');
    }
}
