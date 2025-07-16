<?php

namespace App\Livewire\Waste;

use Livewire\Component;
use Livewire\WithPagination;

use App\Models\Property;
use App\Models\Directory;
use App\Models\Vehicle;
use App\Models\WasteCollectionPriceList;
use App\Models\WasteCollectionSchedule; // Ensure this is correctly imported (use App\Models\WasteCollectionSchedule;)
use App\Models\WasteManagementRegister; // Ensure this is correctly imported
use App\Models\InvoiceSchedule;

use Illuminate\Support\Carbon;
use Throwable;

class WasteManagement extends Component
{
    use WithPagination;

    // Form fields for NEW Registration
    public $property_id;
    public $directories_id;
    public $fk_waste_price_list;
    public $floor;
    public $applicant_is;
    public $block_count;
    public $vehicle_id; // This property MUST be declared here.

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

    public $enable_schedule = false;
    public $enable_invoice = false;

    // Form fields for EDIT Registration
    public $edit_register_id;
    public $edit_property_id;
    public $edit_directories_id;
    public $edit_fk_waste_price_list;
    public $edit_floor;
    public $edit_applicant_is;
    public $edit_block_count;

    public $edit_start_date_collection;
    public $edit_next_collection_date;
    public $edit_recurrence_collection = "daily";
    public $edit_total_cycles_collection = 0;
    public $edit_vehicle_id; // This property MUST be declared here.

    public $edit_start_date_invoice;
    public $edit_next_invoice_date;
    public $edit_recurrence_invoice = "monthly";
    public $edit_total_cycles_invoice = 0;
    public $edit_due_days = 10;
    public $edit_fine_interval = 'daily';
    public $edit_fine_rate = 5;
    public $edit_fine_grace_period = 0;

    public $edit_enable_schedule = false;
    public $edit_enable_invoice = false;

    // Filters
    public $search = '';
    public $statusFilter = '';

    protected $listeners = [
        'resetWasteForm' => 'resetAllFields',
        'openRegistrationModal' => 'openRegistrationModal',
        'startNewRegistration' => 'startNewRegistration',
        'openEditRegistrationModal' => 'openEditRegistrationModal',
        'editWasteRegister' => 'editWasteRegister',
    ];

    /**
     * Set initial default values when the component is first mounted or hydrated.
     */
    public function mount()
    {
        // Ensure initial dates are always set for both new and edit forms
        $this->start_date_collection = Carbon::now()->format('Y-m-d');
        $this->next_collection_date = Carbon::now()->addDay()->format('Y-m-d');
        $this->start_date_invoice = Carbon::now()->format('Y-m-d');
        $this->next_invoice_date = Carbon::now()->addMonth()->format('Y-m-d');

        // Initial values for edit form, will be overwritten when editing
        $this->edit_start_date_collection = Carbon::now()->format('Y-m-d');
        $this->edit_next_collection_date = Carbon::now()->addDay()->format('Y-m-d');
        $this->edit_start_date_invoice = Carbon::now()->format('Y-m-d');
        $this->edit_next_invoice_date = Carbon::now()->addMonth()->format('Y-m-d');
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->dispatch('TableUpdated'); 
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
        $this->dispatch('TableUpdated'); 
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

        // Fetch data for dropdowns (shared for both forms)
        $properties = Property::all();
        $directories = Directory::all();
        $priceLists = WasteCollectionPriceList::all();
        $vehicles = Vehicle::where('status', 'active')->get();

        $this->dispatch('menu-reinit');
        return view('livewire.waste.waste-management', [
            'properties'        => $properties,
            'directories'       => $directories,
            'priceLists'        => $priceLists,
            'vehicles'          => $vehicles,
            'registrations'     => $query->latest()->paginate(10),

            // Pass the same data for edit dropdowns, they are the same lists
            'editProperties'    => $properties,
            'editDirectories'   => $directories,
            'editPriceLists'    => $priceLists,
            'editVehicles'      => $vehicles,
        ])->layout('layouts.master');
    }

    /**
     * Reset all form fields for NEW registration.
     */
    public function resetAllFields()
    {
        $this->reset([
            'property_id',
            'directories_id',
            'fk_waste_price_list',
            'floor',
            'applicant_is',
            'block_count',
            'vehicle_id', // Make sure this is reset
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
        $this->mount(); // Re-initialize default dates
        $this->dispatch('formSubmittedOrReset'); // For the 'add' modal
    }

    /**
     * Reset all form fields for EDIT registration.
     */
    public function resetEditFields()
    {
        $this->reset([
            'edit_register_id',
            'edit_property_id',
            'edit_directories_id',
            'edit_fk_waste_price_list',
            'edit_floor',
            'edit_applicant_is',
            'edit_block_count',
            'edit_vehicle_id',
            'edit_start_date_collection',
            'edit_next_collection_date',
            'edit_recurrence_collection',
            'edit_total_cycles_collection',
            'edit_enable_schedule',
            'edit_enable_invoice',
            'edit_start_date_invoice',
            'edit_next_invoice_date',
            'edit_recurrence_invoice',
            'edit_total_cycles_invoice',
            'edit_due_days',
            'edit_fine_interval',
            'edit_fine_rate',
            'edit_fine_grace_period',
        ]);
        $this->resetValidation();
        $this->mount(); // Re-initialize default dates
        $this->dispatch('editFormSubmittedOrReset'); // For the 'edit' modal
    }

    /**
     * Opens the add registration modal.
     */
    public function openRegistrationModal()
    {
        $this->dispatch('showAddRegisterModal');
    }

    /**
     * Opens the add registration modal and clears the form (for new registration).
     */
    public function startNewRegistration()
    {
        $this->resetAllFields();
        $this->dispatch('showAddRegisterModal');
    }

    /**
     * Opens the edit registration modal.
     */
    public function openEditRegistrationModal()
    {
        $this->dispatch('showEditRegisterModal');
    }

    /**
     * Load data for editing a waste management register.
     * @param string $registerId The UUID of the WasteManagementRegister to edit.
     */
    public function editWasteRegister(string $registerId)
    {
        try {
            $register = WasteManagementRegister::with([
                'wasteCollectionSchedule',
                'invoiceSchedule'
            ])->findOrFail($registerId);


            $this->resetEditFields(); // Clear previous edit state

            $this->edit_register_id = $register->id;
            $this->edit_property_id = $register->property_id;
            $this->edit_directories_id = $register->directories_id;
            $this->edit_fk_waste_price_list = $register->fk_waste_price_list;
            $this->edit_floor = $register->floor;
            $this->edit_applicant_is = $register->applicant_is;
            $this->edit_block_count = $register->block_count;

            // Load Collection Schedule data
            if ($register->wasteCollectionSchedule) {
                $this->edit_enable_schedule = true;
                $this->edit_vehicle_id = $register->wasteCollectionSchedule->vehicle_id;
                $this->edit_start_date_collection = Carbon::parse($register->wasteCollectionSchedule->start_date)->format('Y-m-d');
                $this->edit_next_collection_date = Carbon::parse($register->wasteCollectionSchedule->next_collection_date)->format('Y-m-d');
                $this->edit_recurrence_collection = $register->wasteCollectionSchedule->recurrence;
                $this->edit_total_cycles_collection = $register->wasteCollectionSchedule->total_cycles;
            } else {
                $this->edit_enable_schedule = false;
                // Ensure defaults are set if no schedule exists or reset to default state
                $this->edit_start_date_collection = Carbon::now()->format('Y-m-d');
                $this->edit_next_collection_date = Carbon::now()->addDay()->format('Y-m-d');
                $this->edit_recurrence_collection = "daily";
                $this->edit_total_cycles_collection = 0;
                $this->edit_vehicle_id = null; // Set to null if no schedule
            }

            // Load Invoice Schedule data
            if ($register->invoiceSchedule) {
                $this->edit_enable_invoice = true;
                $this->edit_start_date_invoice = Carbon::parse($register->invoiceSchedule->start_date)->format('Y-m-d');
                $this->edit_next_invoice_date = Carbon::parse($register->invoiceSchedule->next_invoice_date)->format('Y-m-d');
                $this->edit_recurrence_invoice = $register->invoiceSchedule->recurrence;
                $this->edit_total_cycles_invoice = $register->invoiceSchedule->total_cycles;
                $this->edit_due_days = $register->invoiceSchedule->due_days;
                $this->edit_fine_interval = $register->invoiceSchedule->fine_interval;
                $this->edit_fine_rate = $register->invoiceSchedule->fine_rate;
                $this->edit_fine_grace_period = $register->invoiceSchedule->fine_grace_period;
            } else {
                $this->edit_enable_invoice = false;
                // Ensure defaults are set if no invoice schedule exists or reset to default state
                $this->edit_start_date_invoice = Carbon::now()->format('Y-m-d');
                $this->edit_next_invoice_date = Carbon::now()->addMonth()->format('Y-m-d');
                $this->edit_recurrence_invoice = "monthly";
                $this->edit_total_cycles_invoice = 0;
                $this->edit_due_days = 10;
                $this->edit_fine_interval = 'daily';
                $this->edit_fine_rate = 5;
                $this->edit_fine_grace_period = 0;
            }

            $this->dispatch('reinit-edit-select2');

            $this->dispatch('showEditRegisterModal');
        } catch (Throwable $e) {
            \Log::error('Error loading waste register for edit: ' . $e->getMessage(), ['exception' => $e, 'register_id' => $registerId]);
            $this->dispatch('swal', [
                'title' => 'Error!',
                'text' => 'Could not load registration for editing: ' . $e->getMessage(),
                'icon' => 'error',
                'buttonsStyling' => false,
                'confirmButtonText' => 'Ok',
                'confirmButton' => 'btn btn-danger',
            ]);
        }
    }


    /**
     * Save the new registration.
     */
    public function register()
    {
        try {
            // Consolidate all validation rules for new registration
            $rules = [
                'property_id' => 'required|uuid',
                'directories_id' => 'required|uuid',
                'fk_waste_price_list' => 'required|uuid',
                'floor' => 'nullable|string|max:255',
                'applicant_is' => 'required|in:owner,renter',
                'block_count' => 'nullable|integer|min:0|max:12',
                'enable_schedule' => 'boolean', // This binds directly to the checkbox state
                'enable_invoice' => 'boolean', // This binds directly to the checkbox state

                // Conditional validation for Collection Schedule
                'vehicle_id' => 'nullable|uuid|exists:vehicles,id', // Start as nullable, made required by required_if
                'start_date_collection' => 'nullable|date',
                'next_collection_date' => 'nullable|date|after_or_equal:start_date_collection',
                'recurrence_collection' => 'nullable|in:daily,weekly,monthly',
                'total_cycles_collection' => 'nullable|integer|min:0',

                // Conditional validation for Invoice Schedule
                'start_date_invoice' => 'nullable|date',
                'next_invoice_date' => 'nullable|date|after_or_equal:start_date_invoice',
                'recurrence_invoice' => 'nullable|in:daily,weekly,monthly',
                'total_cycles_invoice' => 'nullable|integer|min:0',
                'due_days' => 'nullable|integer|min:0',
                'fine_interval' => 'nullable|in:daily,weekly,monthly',
                'fine_rate' => 'nullable|numeric|min:0',
                'fine_grace_period' => 'nullable|integer|min:0',
            ];

            // Apply `required_if` rules dynamically based on checkbox state
            if ($this->enable_schedule) {
                $rules['vehicle_id'] = 'required|uuid|exists:vehicles,id';
                $rules['start_date_collection'] = 'required|date';
                $rules['next_collection_date'] = 'required|date|after_or_equal:start_date_collection';
                $rules['recurrence_collection'] = 'required|in:daily,weekly,monthly';
            }

            if ($this->enable_invoice) {
                $rules['start_date_invoice'] = 'required|date';
                $rules['next_invoice_date'] = 'required|date|after_or_equal:start_date_invoice';
                $rules['recurrence_invoice'] = 'required|in:daily,weekly,monthly';
                $rules['due_days'] = 'required|integer|min:0';
                $rules['fine_interval'] = 'required|in:daily,weekly,monthly';
                $rules['fine_rate'] = 'required|numeric|min:0';
            }

            $validated = $this->validate($rules);


            $register = WasteManagementRegister::create([
                'property_id' => $this->property_id,
                'directories_id' => $this->directories_id,
                'fk_waste_price_list' => $this->fk_waste_price_list,
                'floor' => $this->floor,
                'applicant_is' => $this->applicant_is,
                'block_count' => $this->block_count ?? 0,
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
                    'recurrence' => $this->recurrence_invoice,
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
            $this->dispatch('refreshComponent'); // Optional: Emit event to refresh table/component

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


    /**
     * Update an existing registration.
     */
    public function update()
    {
        try {
            // Consolidate all validation rules for edit registration
            $rules = [
                'edit_register_id' => 'required|uuid|exists:waste_management_registers,id',
                'edit_property_id' => 'required|uuid',
                'edit_directories_id' => 'required|uuid',
                'edit_fk_waste_price_list' => 'required|uuid',
                'edit_floor' => 'nullable|string|max:255',
                'edit_applicant_is' => 'required|in:owner,renter',
                'edit_block_count' => 'nullable|integer|min:0|max:12',

                'edit_enable_schedule' => 'boolean',
                'edit_enable_invoice' => 'boolean',

                // Conditional validation for Collection Schedule in edit form
                'edit_vehicle_id' => 'nullable|uuid|exists:vehicles,id',
                'edit_start_date_collection' => 'nullable|date',
                'edit_next_collection_date' => 'nullable|date|after_or_equal:edit_start_date_collection',
                'edit_recurrence_collection' => 'nullable|in:daily,weekly,monthly',
                'edit_total_cycles_collection' => 'nullable|integer|min:0',

                // Conditional validation for Invoice Schedule in edit form
                'edit_start_date_invoice' => 'nullable|date',
                'edit_next_invoice_date' => 'nullable|date|after_or_equal:edit_start_date_invoice',
                'edit_recurrence_invoice' => 'nullable|in:daily,weekly,monthly',
                'edit_total_cycles_invoice' => 'nullable|integer|min:0',
                'edit_due_days' => 'nullable|integer|min:0',
                'edit_fine_interval' => 'nullable|in:daily,weekly,monthly',
                'edit_fine_rate' => 'nullable|numeric|min:0',
                'edit_fine_grace_period' => 'nullable|integer|min:0',
            ];

            // Apply `required_if` rules dynamically based on checkbox state
            if ($this->edit_enable_schedule) {
                $rules['edit_vehicle_id'] = 'required|uuid|exists:vehicles,id';
                $rules['edit_start_date_collection'] = 'required|date';
                $rules['edit_next_collection_date'] = 'required|date|after_or_equal:edit_start_date_collection';
                $rules['edit_recurrence_collection'] = 'required|in:daily,weekly,monthly';
            }

            if ($this->edit_enable_invoice) {
                $rules['edit_start_date_invoice'] = 'required|date';
                $rules['edit_next_invoice_date'] = 'required|date|after_or_equal:edit_start_date_invoice';
                $rules['edit_recurrence_invoice'] = 'required|in:daily,weekly,monthly';
                $rules['edit_due_days'] = 'required|integer|min:0';
                $rules['edit_fine_interval'] = 'required|in:daily,weekly,monthly';
                $rules['edit_fine_rate'] = 'required|numeric|min:0';
            }

            $validated = $this->validate($rules);

            $register = WasteManagementRegister::findOrFail($this->edit_register_id);

            $register->update([
                'property_id' => $this->edit_property_id,
                'directories_id' => $this->edit_directories_id,
                'fk_waste_price_list' => $this->edit_fk_waste_price_list,
                'floor' => $this->edit_floor,
                'applicant_is' => $this->edit_applicant_is,
                'block_count' => $this->edit_block_count ?? 0,
            ]);

            // Handle WasteCollectionSchedule update/creation/deletion
            if ($this->edit_enable_schedule) {
                WasteCollectionSchedule::updateOrCreate(
                    ['waste_management_register_id' => $register->id],
                    [
                        'property_id' => $this->edit_property_id,
                        'directories_id' => $this->edit_directories_id,
                        'driver_id' => null,
                        'vehicle_id' => $this->edit_vehicle_id,
                        'start_date' => $this->edit_start_date_collection,
                        'next_collection_date' => $this->edit_next_collection_date,
                        'recurrence' => $this->edit_recurrence_collection,
                        'total_cycles' => $this->edit_total_cycles_collection,
                        'is_active' => true,
                    ]
                );
            } else {
                WasteCollectionSchedule::where('waste_management_register_id', $register->id)->delete();
            }

            // Handle InvoiceSchedule update/creation/deletion
            if ($this->edit_enable_invoice) {
                $priceList = WasteCollectionPriceList::find($this->edit_fk_waste_price_list);
                $lines = [
                    [
                        'description' => $priceList->name,
                        'quantity' => 1,
                        'unit_price' => $priceList->amount,
                    ]
                ];

                InvoiceSchedule::updateOrCreate(
                    ['ref_id' => $register->id, 'invoice_tag' => 'Waste Collection'],
                    [
                        'property_id' => $this->edit_property_id,
                        'directories_id' => $this->edit_directories_id,
                        'start_date' => $this->edit_start_date_invoice,
                        'next_invoice_date' => $this->edit_next_invoice_date,
                        'due_days' => $this->edit_due_days,
                        'total_cycles' => $this->edit_total_cycles_invoice,
                        'fine_interval' => $this->edit_fine_interval,
                        'fine_rate' => $this->edit_fine_rate,
                        'fine_grace_period' => $this->edit_fine_grace_period,
                        'recurrence' => $this->edit_recurrence_invoice,
                        'is_active' => true,
                        'lines' => $lines,
                    ]
                );
            } else {
                InvoiceSchedule::where('ref_id', $register->id)->where('invoice_tag', 'Waste Collection')->delete();
            }

            session()->flash('message', 'Location updated successfully!');
            $this->dispatch('closeEditRegisterModal');
            $this->dispatch('swal', [
                'title' => 'Success',
                'text' => 'Location Updated.',
                'icon' => 'success',
                'buttonsStyling' => false,
                'confirmButtonText' => 'Ok!',
                'confirmButton' => 'btn btn-primary',
            ]);

            $this->resetEditFields();
            $this->dispatch('refreshComponent');

        } catch (Throwable $e) {
            \Log::error('Waste Management Update Error: ' . $e->getMessage(), ['exception' => $e, 'register_id' => $this->edit_register_id ?? 'N/A']);

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