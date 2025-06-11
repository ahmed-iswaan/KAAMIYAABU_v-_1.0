<?php

namespace App\Livewire\Property;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Property;
use App\Models\Island;
use App\Models\Wards;
use App\Models\PropertyTypes;
use Illuminate\Validation\Rule;
use App\Models\EventLog;
use Illuminate\Support\Str;

class PropertyManagement extends Component
{
    use WithPagination;

    public $search    = '';
    public $perPage   = 10;
    public $pageTitle = 'Property Management';

    // Fields for the “Add” / “Edit” form
    public $propertyId;
    public $name;
    public $property_type_id;
    public $register_number;
    public $number;
    public $square_feet;
    public $island_id;
    public $ward_id;
    public $latitude;
    public $longitude;

    

    // Lookup lists
    public $types      = [];
    public $islands    = [];
    public $wards      = [];

    protected $rules = [
        'name'              => 'required|string|max:255',
        'property_type_id'  => 'required|exists:property_types,id',
        'register_number'   => 'required|string|max:100|unique:properties,register_number',
        'number'            => 'required|string|max:100|unique:properties,number',
        'square_feet'       => 'required|numeric|min:0',
        'island_id'         => 'required|exists:islands,id',
        'ward_id'           => 'nullable|exists:wards,id',
        'latitude'          => 'required|numeric',
        'longitude'         => 'required|numeric',
    ];

    protected $validationAttributes = [
        'property_type_id' => 'property type',
        'register_number'  => 'registration number',
        'number'           => 'number',
        'square_feet'      => 'square feet',
        'island_id'        => 'island',
        'ward_id'          => 'ward',
    ];

    protected $listeners = [
        'setLatitude'  => 'setLatitude',
        'setLongitude' => 'setLongitude',
        'showAddModal' => 'openAddModal',
    ];
    
    public function setLatitude($lat)
    {
        $this->latitude = $lat;
    }

    public function setLongitude($lng)
    {
        $this->longitude = $lng;
    }

    public function mount()
    {
        $this->loadLookups();
    }

    public function loadLookups()
    {
        $this->types   = PropertyTypes::orderBy('name')->get();
        $this->islands = Island::with('atoll')->orderBy('name')->get();
        // wards will be loaded after island selection
        $this->wards   = collect();

        $mulah = $this->islands
        ->first(fn($isl) => optional($isl->atoll)->code === 'M' 
                          && Str::contains($isl->name, 'Mulah'));
        $this->island_id = $mulah->id ?? null;
        
    }

    public function updatedIslandId($value)
    {
        $this->wards = Wards::where('island_id', $value)->orderBy('name')->get();
        $this->ward_id = null;
    }

    public function openAddModal()
    {
        // reset fields
        $this->reset(['propertyId','name','property_type_id','register_number','square_feet','island_id','ward_id','latitude','longitude']);
        $this->dispatch('showAddPropertyModal');
    }

    public function createProperty()
    {
        $this->validate();

       $property = Property::create([
            'name'             => $this->name,
            'property_type_id' => $this->property_type_id,
            'register_number'  => $this->register_number,
            'number'           => $this->number,
            'square_feet'      => $this->square_feet,
            'island_id'        => $this->island_id,
            'ward_id'          => $this->ward_id,
            'latitude'         => $this->latitude,
            'longitude'        => $this->longitude,
        ]);

        EventLog::create([
        'user_id'         => auth()->id(),
        'event_tab'       => 'Property',
        'event_entry_id'  => $property->id,
        'event_type'      => 'Property Created',
        'description'     => 'A new property was added.',
        'event_data'      => [
            'name'             => $property->name,
            'register_number'  => $property->register_number,
            'number'           => $property->number,
            'square_feet'      => $property->square_feet,
            'island_id'        => $property->island_id,
            'ward_id'          => $property->ward_id,
            'latitude'         => $property->latitude,
            'longitude'        => $property->longitude,
        ],
        'ip_address'      => request()->ip(),
    ]);

        session()->flash('success', 'Property added successfully.');

        $this->dispatch('swal', [
            'title' => 'Property Created',
            'text' => 'Your new property has been saved.',
            'icon' => 'success',
            'buttonsStyling' => false,
            'confirmButtonText' => 'Great!',
            'confirmButton' => 'btn btn-primary',
        ]);

        $this->dispatch('closeAddPropertyModal');
    }

    public function render()
    {
        $properties = Property::query()
            ->where('name', 'like', "%{$this->search}%")
            ->orWhere('register_number', 'like', "%{$this->search}%")
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.property.property-management', [
            'properties' => $properties,
            'pageTitle'  => $this->pageTitle,
        ])->layout('layouts.master');
    }
}