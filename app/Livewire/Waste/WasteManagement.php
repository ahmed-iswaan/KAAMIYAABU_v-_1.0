<?php

namespace App\Livewire\Waste;

use Livewire\Component;
use Livewire\WithPagination;

use App\Models\Property;
use App\Models\Directory;
use App\Models\Vehicle;
use App\Models\WasteCollectionPriceList;
use App\Models\WasteCollectionSchedule;
use App\Models\WasteManagementRegister;
use App\Models\InvoiceSchedule;

use Illuminate\Support\Carbon;
use Throwable;

class WasteManagement extends Component
{
    use WithPagination;

    // Form fields
    public $property_id;
    public $directories_id;
    public $fk_waste_price_list;
    public $floor;
    public $applicant_is;
    public $start_date;
    public $vehicle_id;
    public $block_count;

    public $start_date_collection;
    public $next_collection_date;
    public $recurrence_collection = "daily";
    public $total_cycles_collection = 0;

    public $start_date_invoice;
    public $next_invoice_date;
    public $recurrence_invoice = "monthly";
    public $total_cycles_invoice = 0;
    public $due_days = 10;
    public $fine_interval = 'daily';
    public $fine_rate = 5;
    public $fine_grace_period = 0;

    // Filters
    public $search = '';
    public $statusFilter = '';

    public $enable_schedule = false;
    public $enable_invoice = false;



    protected $listeners = [
        'resetWasteForm' => 'resetAllFields',
        'openRegistrationModal' => 'openRegistrationModal',
        'startNewRegistration' => 'startNewRegistration',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    /**
     * Render the Livewire component with filters and paginated results.
     */
    public function render()
    {
        $query = WasteManagementRegister::with(['property', 'directory']);

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('number', 'like', '%' . $this->search . '%')
                ->orWhere('register_number', 'like', '%' . $this->search . '%')
                ->orWhereHas('directory', function ($subQ) {
                    $subQ->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('registration_number', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('property', function ($subQ) {
                    $subQ->where('name', 'like', '%' . $this->search . '%');
                });
            });
        }

        // Status filter
        if ($this->statusFilter && $this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return view('livewire.waste.waste-management', [
            'properties'    => Property::all(),
            'directories'   => Directory::all(),
            'priceLists'    => WasteCollectionPriceList::all(),
            'vehicles'      => Vehicle::where('status', 'active')->get(),
            'registrations' => $query->latest()->paginate(10),
        ])->layout('layouts.master');
    }

    /**
     * Reset all form fields.
     */
    public function resetAllFields()
    {
        $this->reset([
            'property_id',
            'directories_id',
            'fk_waste_price_list',
            'floor',
            'applicant_is',
            'start_date',
            'block_count',
            'vehicle_id',
            'start_date_collection',
            'next_collection_date',
            'recurrence_collection',
            'total_cycles_collection',
            'enable_schedule',
            'enable_invoice',

            'start_date_invoice',
            'next_invoice_date',
            'recurrence_invoice',
            'total_cycles_invoice',
            'due_days',
            'fine_interval',
            'fine_rate',
            'fine_grace_period',

        ]);

        $this->resetValidation();

        $this->dispatch('formSubmittedOrReset');
    }

    /**
     * Opens the modal without resetting fields (used for edit case).
     */
    public function openRegistrationModal()
    {
        $this->dispatch('showAddRegisterModal');
    }

    /**
     * Opens the modal and clears the form (used for new registration).
     */
    public function startNewRegistration()
    {
        $this->resetAllFields();
        $this->dispatch('showAddRegisterModal');
    }

    /**
     * Save the new registration.
     */
    public function register()
{
    try {
        $validated = $this->validate([
            'property_id' => 'required|uuid',
            'directories_id' => 'required|uuid',
            'fk_waste_price_list' => 'required|uuid',
            'floor' => 'nullable|string|max:255',
            'applicant_is' => 'required|in:owner,renter',
            'vehicle_id' => 'nullable|uuid|exists:vehicles,id',
        ]);


        $register = WasteManagementRegister::create([
            'property_id' => $this->property_id,
            'directories_id' => $this->directories_id,
            'fk_waste_price_list' => $this->fk_waste_price_list,
            'floor' => $this->floor,
            'applicant_is' => $this->applicant_is,
            'block_count' => $this->block_count,
            'status' => 'Active',
        ]);

        if ($this->enable_schedule) {

            WasteCollectionSchedule::create([
                'property_id' => $this->property_id,
                'directories_id' => $this->directories_id,
                'waste_management_register_id' => $register->id,
                'driver_id' => null,
                'vehicle_id' => $this->vehicle_id,
                'start_date' => $this->start_date_collection,
                'next_collection_date' => $this->next_collection_date,
                'recurrence' => $this->recurrence_collection,
                'total_cycles' => $this->total_cycles_collection,
                'is_active' => true,
            ]);
        }

        if ($this->enable_invoice) {
            $priceList = WasteCollectionPriceList::find($this->fk_waste_price_list);
            $lines = [
                [
                    'description' => $priceList->name,
                    'quantity' => 1,
                    'unit_price' => $priceList->amount,
                ]
            ];

            InvoiceSchedule::create([
                'property_id' => $this->property_id,
                'directories_id' => $this->directories_id,
                'start_date' => $this->start_date_invoice,
                'next_invoice_date' => $this->next_invoice_date,
                'due_days' => $this->due_days,
                'total_cycles' => $this->total_cycles_invoice,
                'invoice_tag' => 'Waste Collection',
                'ref_id' => $register->id,
                'fine_interval' => $this->fine_interval,
                'fine_rate' => $this->fine_rate,
                'fine_grace_period' => $this->fine_grace_period,
                'recurrence' =>  $this->recurrence_invoice,
                'is_active' => true,
                'lines' => $lines,
            ]);
        }

        session()->flash('message', 'Location registered successfully!');
        $this->dispatch('closeAddRegisterModal');
        $this->dispatch('swal', [
            'title' => 'Success',
            'text' => 'Location Registered.',
            'icon' => 'success',
            'buttonsStyling' => false,
            'confirmButtonText' => 'Ok!',
            'confirmButton' => 'btn btn-primary',
        ]);

        $this->resetAllFields();

    } catch (Throwable $e) {
        \Log::error('Waste Management Registration Error: ' . $e->getMessage(), ['exception' => $e]);

        $this->dispatch('swal', [
            'title' => 'Error!',
            'text' => 'An unexpected error occurred: ' . $e->getMessage(),
            'icon' => 'error',
            'buttonsStyling' => false,
            'confirmButtonText' => 'Ok',
            'confirmButton' => 'btn btn-danger',
        ]);
    }
}

}
