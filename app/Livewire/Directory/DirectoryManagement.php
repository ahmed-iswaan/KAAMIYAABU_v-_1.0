<?php

namespace App\Livewire\Directory;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Models\Directory;
use App\Models\Country;
use App\Models\Island;
use App\Models\Property;
use App\Models\DirectoryRelationship;
use App\Models\PendingTelegramNotification;
use App\Models\EventLog;
use App\Models\Party;
use App\Models\Consite;
use App\Models\SubConsite;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Cache; // added
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Models\DirectoryPhoneStatus;

class DirectoryManagement extends Component
{
    use WithPagination, WithFileUploads,AuthorizesRequests;

    // Added property to fix missing wire:model warning
    public bool $profile_picture_remove = false;

    public $search = '';
    public $perPage = 10;
    public $pageTitle = 'Directory';

    // SubConsite filter
    public $filter_sub_consite_id = null;
    public $filter_gender = null; // 'male' | 'female' | 'other'

    public $editingDirectoryId;

    // New schema fields (Add Form)
    public $name, $description, $profile_picture;
    public $id_card_number;
    public $gender = 'other';
    public $date_of_birth, $death_date;
    public $phones = []; // array of phone strings
    public $email, $website;

    // Permanent location
    public $country_id, $island_id, $properties_id, $address, $street_address;

    // Current location
    public $current_country_id, $current_island_id, $current_properties_id, $current_address, $current_street_address;

    // Party / Election related
    public $party_id, $consite_id, $sub_consite_id;
    public $consite_or_sub_id; // unified select (will hold either a consite id or sub consite id)

    public $status = 'Active';

    // Contact person (kept for compatibility)
    public bool $has_contact_person = false;
    public ?string $contact_directory_id = null;
    public ?string $contact_designation = null;

    // Visibility helpers
    public $is_island_visible = false;            // permanent
    public $is_property_visible = false;          // permanent
    public $is_current_island_visible = false;    // current
    public $is_current_property_visible = false;  // current

    // Add cached Maldives country id
    public $maldivesCountryId; 

    // Edit form fields (prefixed)
    public $edit_name, $edit_description, $edit_profile_picture, $edit_profile_picture_path;
    public $edit_id_card_number;
    public $edit_gender = 'other';
    public $edit_date_of_birth, $edit_death_date;
    public $edit_phones = [];
    public $edit_email, $edit_website;
    public $edit_country_id, $edit_island_id, $edit_properties_id, $edit_address, $edit_street_address;
    public $edit_current_country_id, $edit_current_island_id, $edit_current_properties_id, $edit_current_address, $edit_current_street_address;
    public $edit_party_id, $edit_consite_id, $edit_sub_consite_id;
    public $edit_consite_or_sub_id; // unified select for edit
    public $edit_status = 'Active';

    public bool $edit_has_contact_person = false;
    public ?string $edit_contact_directory_id = null;
    public ?string $edit_contact_designation = null;

    public $edit_is_island_visible = false;
    public $edit_is_property_visible = false;
    public $edit_is_current_island_visible = false;
    public $edit_is_current_property_visible = false;

    // Reference datasets (lightweight always-resident)
    public $countries = [];
    public $parties = [];
    public $consites = [];
    public $contacts = [];
    // Lazily loaded larger sets
    public $islands;
    public $properties;
    protected $referenceLoaded = false;

    // Call status (per number)
    public array $phoneCallStatuses = []; // [normalizedPhone => status]
    public array $phoneCallNotes = [];    // [normalizedPhone => notes]

    public function mount()
    {
        $this->islands = collect([]);
        $this->properties = collect([]);
        $this->maldivesCountryId = Country::where('name', 'Maldives')->value('id');
        if (empty($this->phones)) { $this->phones = ['']; }
        if (empty($this->edit_phones)) { $this->edit_phones = ['']; }
        // Preload lightweight reference data so options exist on first render
        $this->loadReferenceData();
    }

    protected function loadReferenceData(): void
    {
        if ($this->referenceLoaded) { return; }
        $this->countries = Cache::remember('dir_ref_countries', 1800, fn()=> Country::select('id','name')->orderBy('name')->get());
        $this->parties   = Cache::remember('dir_ref_parties', 1800, fn()=> Party::select('id','name','short_name','logo')->orderBy('name')->get());
        $this->consites  = Cache::remember('dir_ref_consites_small', 1800, fn()=> Consite::select('id','name')->with(['subConsites:id,consite_id,code,name'])->orderBy('name')->get());
        $this->contacts  = Cache::remember('dir_ref_contacts_small', 600, fn()=> Directory::select('id','name','id_card_number')->where('status','Active')->orderBy('name')->limit(150)->get());
        $this->referenceLoaded = true;
        $this->dispatch('reference-data-loaded');
    }

    public function testFetchIslands()
    {
        try {
            // Direct database query without cache
            $islands = Island::select('id','name','atoll_id')->with('atoll:id,code')->orderBy('name')->get();
            $this->dispatch('debug-log', ['message' => 'Direct DB query found ' . $islands->count() . ' islands']);
            
            if ($islands->count() > 0) {
                $firstIsland = $islands->first();
                $this->dispatch('debug-log', ['message' => 'First island: ' . $firstIsland->name . ' (ID: ' . $firstIsland->id . ')']);
            }
            
            return $islands;
        } catch (\Exception $e) {
            $this->dispatch('debug-log', ['message' => 'Direct DB query error: ' . $e->getMessage()]);
            return collect([]);
        }
    }

    protected function fetchIslands(): void
    {
        try {
            $totalInDb = Island::count();
            $this->dispatch('debug-log', ['message' => 'fetchIslands start. Total islands in DB: '.$totalInDb]);

            // Direct (uncached) query for debugging reliability
            $islands = Island::select('id','name','atoll_id')->with('atoll:id,code')->orderBy('name')->get();
            $this->islands = $islands instanceof \Illuminate\Support\Collection ? $islands : collect($islands);

            $this->dispatch('debug-log', ['message' => 'Direct query returned '.$this->islands->count().' islands']);

            // Fallback: if still zero but DB reports >0, run a raw minimal query
            if ($this->islands->isEmpty() && $totalInDb > 0) {
                $this->dispatch('debug-log', ['message' => 'Primary fetch empty, attempting fallback raw query']);
                $fallback = Island::query()->select('id','name')->limit(2000)->get();
                $this->dispatch('debug-log', ['message' => 'Fallback query returned '.$fallback->count().' islands']);
                if($fallback->isNotEmpty()) { $this->islands = $fallback; }
            }

            $this->dispatch('reference-data-loaded');
            $this->syncProperties();
        } catch (\Exception $e) {
            $this->dispatch('debug-log', ['message' => 'Error fetching islands: '.$e->getMessage()]);
            $this->islands = collect([]);
        }
    }

    // Make syncProperties public so it can be explicitly called from JS if needed
    public function syncProperties(): void
    {
        $islandIds = collect([
            $this->island_id,
            $this->current_island_id,
            $this->edit_island_id,
            $this->edit_current_island_id
        ])->filter()->unique()->values()->all();

        if (empty($islandIds)) {
            $this->properties = collect([]);
        } else {
            $LIMIT = 600;
            $baseQuery = Property::select('id','name','island_id')->whereIn('island_id', $islandIds)->orderBy('name');
            $properties = $baseQuery->limit($LIMIT)->get();
            $selectedIds = collect([
                $this->properties_id,
                $this->current_properties_id,
                $this->edit_properties_id,
                $this->edit_current_properties_id
            ])->filter()->unique()->diff($properties->pluck('id'))->values();
            if ($selectedIds->isNotEmpty()) {
                $extra = Property::select('id','name','island_id')->whereIn('id', $selectedIds)->get();
                $properties = $properties->concat($extra)->unique('id')->values();
            }
            $this->properties = $properties;
        }
    }

    // --- Updated handlers (add) ---
    public function updatedCountryId($value)
    {
        $this->dispatch('debug-log', ['message' => 'Country changed to: ' . $value]);
        $this->is_island_visible = $this->countryHasIslands($value);
        $this->dispatch('debug-log', ['message' => 'is_island_visible => '.($this->is_island_visible?'true':'false')]);
        $this->island_id = null;
        $this->properties_id = null;
        $this->is_property_visible = false;
        if ($this->is_island_visible) {
            $this->fetchIslands();
            $this->dispatch('debug-log', ['message' => 'After fetchIslands islands count: '.$this->islands->count()]);
        }
        $this->syncProperties();
    }
    public function updatedIslandId($value)
    {
        $this->properties_id = null;
        $this->is_property_visible = $value ? Property::where('island_id', $value)->exists() : false;
        $this->dispatch('debug-log', ['message' => 'Island changed to: '.$value.' | is_property_visible => '.($this->is_property_visible?'true':'false')]);
        if (!$this->is_property_visible) {
            $this->properties_id = null;
        }
        $this->syncProperties();
    }
    public function updatedCurrentCountryId($value)
    {
        $this->is_current_island_visible = $this->countryHasIslands($value);
        $this->current_island_id = null;
        $this->current_properties_id = null;
        $this->is_current_property_visible = false;

        if ($this->is_current_island_visible) {
            $this->fetchIslands();
        }
        $this->syncProperties();
    }
    public function updatedCurrentIslandId($value)
    {
        $this->current_properties_id = null;
        $this->is_current_property_visible = !empty($value);
        $this->syncProperties();
    }

    // --- Updated handlers (edit) ---
    public function updatedEditCountryId($value)
    {
        $this->edit_is_island_visible = $this->countryHasIslands($value);
        $this->edit_island_id = null;
        $this->edit_properties_id = null;
        $this->edit_is_property_visible = false;

        if ($this->edit_is_island_visible) {
            $this->fetchIslands();
        }
        $this->syncProperties();
        $this->dispatch('reinit-edit-select2');
    }
    public function updatedEditIslandId($value)
    {
        $this->edit_properties_id = null;
        $this->edit_is_property_visible = $value ? Property::where('island_id', $value)->exists() : false;
        $this->syncProperties();
        $this->dispatch('reinit-edit-select2');
    }
    public function updatedEditCurrentCountryId($value)
    {
        $this->edit_is_current_island_visible = $this->countryHasIslands($value);
        $this->edit_current_island_id = null;
        $this->edit_current_properties_id = null;
        $this->edit_is_current_property_visible = false;

        if ($this->edit_is_current_island_visible) {
            $this->fetchIslands();
        }
        $this->syncProperties();
        $this->dispatch('reinit-edit-select2');
    }
    public function updatedEditCurrentIslandId($value)
    {
        $this->edit_current_properties_id = null;
        $this->edit_is_current_property_visible = $value ? Property::where('island_id', $value)->exists() : false;
        $this->syncProperties();
        $this->dispatch('reinit-edit-select2');
    }

    /* -------------------- Dynamic Phone Helpers -------------------- */
    public function addPhoneField()
    {
        $this->phones[] = '';
    }
    public function removePhoneField($index)
    {
        if (isset($this->phones[$index])) {
            unset($this->phones[$index]);
            $this->phones = array_values($this->phones);
        }
    }
    public function editAddPhoneField()
    {
        $this->edit_phones[] = '';
    }
    public function editRemovePhoneField($index)
    {
        if (isset($this->edit_phones[$index])) {
            unset($this->edit_phones[$index]);
            $this->edit_phones = array_values($this->edit_phones);
        }
    }

    /* -------------------- Location Visibility Logic -------------------- */
    protected function countryHasIslands($countryId): bool
    {
        // Refresh cached Maldives id if missing
        if (!$this->maldivesCountryId) {
            $this->maldivesCountryId = Country::where('name', 'Maldives')->value('id');
        }
        
        $this->dispatch('debug-log', ['message' => "Checking country: $countryId, Maldives ID: {$this->maldivesCountryId}"]);
        
        if (empty($countryId) || empty($this->maldivesCountryId)) {
            $this->dispatch('debug-log', ['message' => 'Country or Maldives ID is empty']);
            return false;
        }
        // Use loose comparison cast to string to avoid strict UUID object vs string mismatch
        $result = (string)$countryId === (string)$this->maldivesCountryId;
        $this->dispatch('debug-log', ['message' => "Country check: $countryId vs {$this->maldivesCountryId} = " . ($result ? 'true' : 'false')]);
        return $result;
    }

    /* -------------------- Consite/SubConsite Logic -------------------- */
    public function updatedConsiteId($value)
    {
        $this->sub_consite_id = null;
    }

    /* -------------------- Render -------------------- */
    public function render()
    {
        $this->authorize('directory-render');
        $directory = Directory::with([
                'party:id,short_name,name,logo',
                'subConsite:id,code,name',
                'property:id,name',
                'island:id,name,atoll_id',
                'island.atoll:id,code',
                'country:id,name',
                'currentProperty:id,name',
                'currentIsland:id,name,atoll_id',
                'currentIsland.atoll:id,code',
                'currentCountry:id,name'
            ])
            ->select([
                'id','name','profile_picture','id_card_number','gender','date_of_birth','phones','email',
                'party_id','sub_consite_id','properties_id','street_address','address','country_id','island_id',
                'current_properties_id','current_street_address','current_address','current_country_id','current_island_id',
                'status','created_at'
            ])
            ->when($this->search, function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function($qq) use ($term){
                    $qq->where('name','like',$term)
                       ->orWhere('email','like',$term)
                       ->orWhere('id_card_number','like',$term);
                });
            })
            ->when($this->filter_sub_consite_id, function($q){
                $q->where('sub_consite_id', $this->filter_sub_consite_id);
            })
            // Gender filter: other => NULL or not in male/female; male/female direct match
            ->when($this->filter_gender === 'other', function($q){
                $q->where(function($qq){
                    $qq->whereNull('gender')
                       ->orWhereNotIn('gender', ['male','female']);
                });
            })
            ->when(in_array($this->filter_gender, ['male','female'], true), function($q){
                $q->where('gender', $this->filter_gender);
            })
            ->latest()
            ->paginate($this->perPage);

        // Status totals (overall)
        $totalActive = Directory::where('status','Active')->count();
        $totalInactive = Directory::where('status','Inactive')->count();

        return view('livewire.directory.directory-management', [
            'directory' => $directory,
            'pageTitle' => $this->pageTitle,
            'countries' => $this->countries,
            'islands' => $this->islands,
            'properties' => $this->properties,
            'parties' => $this->parties,
            'consites' => $this->consites,
            'contacts' => $this->contacts,
            'totalActive' => $totalActive,
            'totalInactive' => $totalInactive,
            'filter_sub_consite_id' => $this->filter_sub_consite_id,
            'filter_gender' => $this->filter_gender,
        ])->layout('layouts.master');
    }

    public function openAddModal()
    {
        $this->dispatch('debug-log', ['message' => 'openAddModal called']);
        $this->loadReferenceData();
        $this->resetForm();
        $this->dispatch('debug-log', ['message' => 'About to dispatch showAddDirectoryModal']);
        $this->dispatch('showAddDirectoryModal');
        $this->dispatch('debug-log', ['message' => 'showAddDirectoryModal dispatched']);
    }

    public function openEdit()
    {
        $this->dispatch('showEditDirectoryModal');
    }

    public function openEditModal($id)
    {
        $this->loadReferenceData();
        $this->editingDirectoryId = $id;
        $directory = Directory::with(['country','island','property','currentCountry','currentIsland','currentProperty','party','subConsite','contactPersonRelationship.linkedDirectory'])->find($id);
        if (!$directory) {
            $this->dispatch('swal', [ 'title' => 'Error', 'text' => 'Directory not found.', 'icon' => 'error', 'confirmButtonText' => 'Ok!', 'confirmButton' => 'btn btn-primary' ]);
            return;
        }

        // Populate edit form
        $this->edit_name = $directory->name;
        $this->edit_description = $directory->description;
        $this->edit_profile_picture_path = $directory->profile_picture;
        $this->edit_id_card_number = $directory->id_card_number;
        $this->edit_gender = $directory->gender;
        $this->edit_date_of_birth = $directory->date_of_birth;        
        $this->edit_death_date = $directory->death_date;        
        $this->edit_phones = $directory->phones ?: [''];
        $this->edit_email = $directory->email;
        $this->edit_website = $directory->website;
        $this->edit_country_id = $directory->country_id;
        $this->edit_island_id = $directory->island_id;
        $this->edit_properties_id = $directory->properties_id;
        $this->edit_address = $directory->address;
        $this->edit_street_address = $directory->street_address;
        $this->edit_current_country_id = $directory->current_country_id;
        $this->edit_current_island_id = $directory->current_island_id;
        $this->edit_current_properties_id = $directory->current_properties_id;
        $this->edit_current_address = $directory->current_address;
        $this->edit_current_street_address = $directory->current_street_address;
        $this->edit_party_id = $directory->party_id;
        $this->edit_sub_consite_id = $directory->sub_consite_id;
        $this->edit_consite_id = optional($directory->subConsite)->consite_id; // for filtering
        $this->edit_consite_or_sub_id = $this->edit_sub_consite_id ?: $this->edit_consite_id;
        $this->edit_status = $directory->status;

        // Pre-fetch islands if needed
        if ($this->countryHasIslands($this->edit_country_id) || $this->countryHasIslands($this->edit_current_country_id)) {
            $this->fetchIslands();
        }
        
        // Sync properties for selected islands
        $this->syncProperties();

        // Visibility recalculation (direct, no event handlers)
        $this->edit_is_island_visible = $this->countryHasIslands($this->edit_country_id);
        $this->edit_is_property_visible = $this->edit_island_id && $this->properties->where('island_id', $this->edit_island_id)->count() > 0;
        $this->edit_is_current_island_visible = $this->countryHasIslands($this->edit_current_country_id);
        $this->edit_is_current_property_visible = $this->edit_current_island_id && $this->properties->where('island_id', $this->edit_current_island_id)->count() > 0;

        // Contact person
        $relationship = DirectoryRelationship::where('directory_id', $this->editingDirectoryId)->first();
        if ($relationship) {
            $this->edit_has_contact_person = true;
            $this->edit_contact_directory_id = $relationship->linked_directory_id;
            $this->edit_contact_designation = $relationship->designation;
        } else {
            $this->edit_has_contact_person = false;
            $this->edit_contact_directory_id = null;
            $this->edit_contact_designation = null;
        }

        $this->dispatch('reinit-edit-select2');

        // Call Status (per number)
        $this->loadPhoneCallStatuses((string) $id);

        $this->openEdit();
    }

    /* -------------------- Save -------------------- */
    public function save()
    {
        $this->validate($this->rules());
        if($this->consite_or_sub_id){
            $this->sub_consite_id = $this->consite_or_sub_id;
        }

        $directory = new Directory();
        $directory->id = Str::uuid();
        $directory->name = $this->name;
        $directory->description = $this->description;
        $directory->id_card_number = $this->id_card_number;
        $directory->gender = $this->gender;
        $directory->date_of_birth = $this->date_of_birth;
        $directory->death_date = $this->death_date;
        $directory->phones = $this->cleanPhones($this->phones);
        $directory->email = $this->email;
        $directory->website = $this->website;
        $directory->country_id = $this->country_id;
        $directory->island_id = $this->island_id;
        $directory->properties_id = $this->properties_id;
        $directory->address = $this->address;
        $directory->street_address = $this->street_address;
        $directory->current_country_id = $this->current_country_id;
        $directory->current_island_id = $this->current_island_id;
        $directory->current_properties_id = $this->current_properties_id;
        $directory->current_address = $this->current_address;
        $directory->current_street_address = $this->current_street_address;
        $directory->party_id = $this->party_id;
        $directory->sub_consite_id = $this->sub_consite_id;
        $directory->status = $this->status ?: 'Active';

        if ($this->profile_picture) {
            $directory->profile_picture = $this->profile_picture->store('directory/profiles', 'public');
        }

        $directory->save();

        if ($this->has_contact_person) {
            $this->createOrUpdateRelationship($directory->id, $this->contact_directory_id, $this->contact_designation);
        }

        $this->logAndNotify('Directory Created', $directory);

        $this->dispatch('swal', [
            'title' => 'Created',
            'text' => 'Directory added successfully.',
            'icon' => 'success',
            'confirmButtonText' => 'Ok!',
            'confirmButton' => 'btn btn-primary'
        ]);

        session()->flash('success', 'Directory added successfully.');
        $this->resetForm();
        $this->dispatch('closeAddDirectoryModal');
    }

    /* -------------------- Edit -------------------- */
    public function edit()
    {
        $directory = Directory::find($this->editingDirectoryId);
        if (!$directory) {
            $this->dispatch('swal', [
                'title' => 'Error',
                'text' => 'Directory not found.',
                'icon' => 'error',
                'confirmButtonText' => 'Ok!',
                'confirmButton' => 'btn btn-primary'
            ]);
            return;
        }

        $this->validate($this->editRules($directory->id));
        if($this->edit_consite_or_sub_id){
            $this->edit_sub_consite_id = $this->edit_consite_or_sub_id;
        }

        $directory->name = $this->edit_name;
        $directory->description = $this->edit_description;
        $directory->id_card_number = $this->edit_id_card_number;
        $directory->gender = $this->edit_gender;
        $directory->date_of_birth = $this->edit_date_of_birth;
        $directory->death_date = $this->edit_death_date;
        $directory->phones = $this->cleanPhones($this->edit_phones);
        $directory->email = $this->edit_email;
        $directory->website = $this->edit_website;
        $directory->country_id = $this->edit_country_id;
        $directory->island_id = $this->edit_island_id;
        $directory->properties_id = $this->edit_properties_id;
        $directory->address = $this->edit_address;
        $directory->street_address = $this->edit_street_address;
        $directory->current_country_id = $this->edit_current_country_id;
        $directory->current_island_id = $this->edit_current_island_id;
        $directory->current_properties_id = $this->edit_current_properties_id;
        $directory->current_address = $this->edit_current_address;
        $directory->current_street_address = $this->edit_current_street_address;
        $directory->party_id = $this->edit_party_id;
        $directory->sub_consite_id = $this->edit_sub_consite_id;
        $directory->status = $this->edit_status ?: 'Active';

        if ($this->edit_profile_picture) {
            if ($directory->profile_picture) { \Storage::disk('public')->delete($directory->profile_picture); }
            $directory->profile_picture = $this->edit_profile_picture->store('directory/profiles', 'public');
        }
        $directory->save();

        // Relationship
        $relationship = DirectoryRelationship::where('directory_id', $directory->id)->first();
        if ($this->edit_has_contact_person) {
            $this->createOrUpdateRelationship($directory->id, $this->edit_contact_directory_id, $this->edit_contact_designation, $relationship);
        } elseif ($relationship) {
            $relationship->delete();
        }

        $this->logAndNotify('Directory Updated', $directory);

        $this->dispatch('swal', [
            'title' => 'Updated',
            'text' => 'Directory updated successfully.',
            'icon' => 'success',
            'confirmButtonText' => 'Ok!',
            'confirmButton' => 'btn btn-primary'
        ]);

        session()->flash('success', 'Directory updated successfully.');
        $this->resetEditForm();
        $this->dispatch('closeEditDirectoryModal');
    }

    /* -------------------- Validation Rules -------------------- */
    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'id_card_number' => 'nullable|string|max:255|unique:directories,id_card_number',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'death_date' => 'nullable|date|after_or_equal:date_of_birth',
            'phones' => 'nullable|array|min:1',
            'phones.*' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255|unique:directories,email',
            'website' => 'nullable|url|max:255',
            'country_id' => 'nullable|uuid|exists:countries,id',
            'island_id' => 'nullable|uuid|exists:islands,id',
            'properties_id' => 'nullable|uuid|exists:properties,id',
            'address' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'current_country_id' => 'nullable|uuid|exists:countries,id',
            'current_island_id' => 'nullable|uuid|exists:islands,id',
            'current_properties_id' => 'nullable|uuid|exists:properties,id',
            'current_address' => 'nullable|string|max:255',
            'current_street_address' => 'nullable|string|max:255',
            'party_id' => 'nullable|uuid|exists:parties,id',
            'consite_or_sub_id' => 'nullable|uuid|exists:sub_consites,id',
            'sub_consite_id' => 'nullable|uuid|exists:sub_consites,id',
            'status' => 'nullable|string|max:50',
            'profile_picture' => 'nullable|image|max:1024',
            'has_contact_person' => 'boolean',
            'contact_directory_id' => Rule::requiredIf($this->has_contact_person).'|nullable|uuid|exists:directories,id',
            'contact_designation' => 'nullable|string|max:255',
        ];
    }

    protected function editRules($id): array
    {
        return [
            'edit_name' => 'required|string|max:255',
            'edit_description' => 'nullable|string',
            'edit_id_card_number' => 'nullable|string|max:255|unique:directories,id_card_number,'.$id.',id',
            'edit_gender' => 'nullable|in:male,female,other',
            'edit_date_of_birth' => 'nullable|date',
            'edit_death_date' => 'nullable|date|after_or_equal:edit_date_of_birth',
            'edit_phones' => 'nullable|array|min:1',
            'edit_phones.*' => 'nullable|string|max:30',
            'edit_email' => 'nullable|email|max:255|unique:directories,email,'.$id.',id',
            'edit_website' => 'nullable|url|max:255',
            'edit_country_id' => 'nullable|uuid|exists:countries,id',
            'edit_island_id' => 'nullable|uuid|exists:islands,id',
            'edit_properties_id' => 'nullable|uuid|exists:properties,id',
            'edit_address' => 'nullable|string|max:255',
            'edit_street_address' => 'nullable|string|max:255',
            'edit_current_country_id' => 'nullable|uuid|exists:countries,id',
            'edit_current_island_id' => 'nullable|uuid|exists:islands,id',
            'edit_current_properties_id' => 'nullable|uuid|exists:properties,id',
            'edit_current_address' => 'nullable|string|max:255',
            'edit_current_street_address' => 'nullable|string|max:255',
            'edit_party_id' => 'nullable|uuid|exists:parties,id',
            'edit_consite_or_sub_id' => 'nullable|uuid|exists:sub_consites,id',
            'edit_sub_consite_id' => 'nullable|uuid|exists:sub_consites,id',
            'edit_status' => 'nullable|string|max:50',
            'edit_profile_picture' => 'nullable|image|max:1024',
            'edit_has_contact_person' => 'boolean',
            'edit_contact_directory_id' => Rule::requiredIf($this->edit_has_contact_person).'|nullable|uuid|exists:directories,id',
            'edit_contact_designation' => 'nullable|string|max:255',
        ];
    }

    protected function cleanPhones(array $phones): array
    {
        return array_values(array_filter(array_map(function($p){
            return $p !== null ? trim($p) : null;
        }, $phones), function($p){ return $p !== '' && $p !== null; }));
    }

    protected function createOrUpdateRelationship($directoryId, $linkedId, $designation, $relationship = null)
    {
        if ($relationship) {
            $relationship->linked_directory_id = $linkedId;
            $relationship->designation = $designation;
            $relationship->save();
        } else {
            $relationship = new DirectoryRelationship();
            $relationship->directory_id = $directoryId;
            $relationship->linked_directory_id = $linkedId;
            $relationship->link_type = 'linked';
            $relationship->designation = $designation;
            $relationship->save();
        }
    }

    protected function logAndNotify($event, Directory $directory)
    {
        PendingTelegramNotification::create([
            'chat_id' => env('TELEGRAM_GROUP_DIRECTORY'),
            'message_thread_id' => env('TELEGRAM_TOPIC_DIRECTORY'),
            'message' => $this->buildTelegramMessage($event, $directory),
        ]);

        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Directory',
            'event_entry_id' => $directory->id,
            'event_type' => $event,
            'description' => $event.' entry.',
            'event_data' => $directory->toArray(),
            'ip_address' => request()->ip(),
        ]);
    }

    protected function buildTelegramMessage($event, $directory)
    {
        $envLabel = app()->environment('production') ? '🟢 Production' : '🧪 Development';
        $phonesList = $directory->phones ? implode(', ', $directory->phones) : 'N/A';
        return "<b>📁 Directory {$event}</b>\n" .
            "<i>{$envLabel} Environment</i>\n" .
            "──────────────────────────────\n" .
            "<b>🏷️ Name:</b> {$directory->name}\n" .
            "<b>🆔 ID Card:</b> ".($directory->id_card_number ?? 'N/A')."\n" .
            "<b>📞 Phones:</b> {$phonesList}\n" .
            "<b>📧 Email:</b> ".($directory->email ?? 'N/A')."\n" .
            "<b>🎉 Party:</b> ".optional($directory->party)->short_name."\n" .
            "<b>🏝️ Island:</b> ".optional($directory->island)->name."\n" .
            "<b>📍 Address:</b> ".($directory->street_address ?? 'N/A')."\n" .
            "──────────────────────────────\n" .
            "<b>👤 By:</b> ".auth()->user()->name."\n" .
            "<b>🕒 At:</b> ".now()->format('d M Y H:i');
    }

    /* -------------------- Reset Forms -------------------- */
    public function resetForm()
    {
        $this->reset([
            'name','description','profile_picture','id_card_number','gender','date_of_birth','death_date',
            'phones','email','website','country_id','island_id','properties_id','address','street_address',
            'current_country_id','current_island_id','current_properties_id','current_address','current_street_address',
            'party_id','consite_id','sub_consite_id','status','has_contact_person','contact_directory_id','contact_designation','consite_or_sub_id'
        ]);
        $this->phones = [''];
        $this->status = 'Active';
        $this->profile_picture_remove = false; // ensure reset
        $this->dispatch('formSubmittedOrReset');
    }

    public function resetEditForm()
    {
        $this->reset([
            'editingDirectoryId','edit_name','edit_description','edit_profile_picture','edit_id_card_number','edit_gender','edit_date_of_birth','edit_death_date',
            'edit_phones','edit_email','edit_website','edit_country_id','edit_island_id','edit_properties_id','edit_address','edit_street_address',
            'edit_current_country_id','edit_current_island_id','edit_current_properties_id','edit_current_address','edit_current_street_address',
            'edit_party_id','edit_consite_id','edit_sub_consite_id','edit_status','edit_has_contact_person','edit_contact_directory_id','edit_contact_designation','edit_consite_or_sub_id'
        ]);
        $this->edit_phones = [''];
        $this->profile_picture_remove = false;
        $this->dispatch('editFormSubmittedOrReset');
    }

    public function testSimple()
    {
        $this->dispatch('debug-log', ['message' => 'Simple test button clicked!']);
    }

    public function loadTestIslands()
    {
        try {
            $this->dispatch('debug-log', ['message' => 'Test islands method called']);
            
            // Force load islands directly
            $islands = Island::select('id','name','atoll_id')->with('atoll:id,code')->orderBy('name')->limit(10)->get();
            $this->islands = $islands;
            
            $this->dispatch('debug-log', ['message' => 'Test: Loaded ' . $islands->count() . ' islands directly']);
            
            // Force dispatch islands to frontend
            $islandOptions = $islands->map(fn($i) => [
                'id' => $i->id, 
                'text' => ($i->atoll?->code ?? 'N/A') . '. ' . $i->name
            ])->all();
            
            $this->dispatch('update-options', [
                'id' => 'kt_select2_add_island_id',
                'options' => $islandOptions,
                'placeholder' => 'Select Island'
            ]);
            
            $this->dispatch('debug-log', ['message' => 'Test: Dispatched ' . count($islandOptions) . ' island options']);
            
        } catch (\Exception $e) {
            $this->dispatch('debug-log', ['message' => 'Test error: ' . $e->getMessage()]);
        }
    }

    public function refreshIslands()
    {
        // Force re-fetch (bypass cache) and re-dispatch options
        $this->fetchIslands();
        $this->syncProperties();
        $this->dispatch('debug-log', ['message' => 'refreshIslands called -> islands: '.$this->islands->count()]);
    }

    protected function loadPhoneCallStatuses(?string $directoryId): void
    {
        $this->phoneCallStatuses = [];
        $this->phoneCallNotes = [];

        if (!$directoryId) return;

        $directory = Directory::with('phoneStatuses')->find($directoryId);
        if (!$directory) return;

        foreach (($directory->phones ?? []) as $p) {
            $norm = DirectoryPhoneStatus::normalizePhone($p);
            if (!$norm) continue;
            $this->phoneCallStatuses[$norm] = DirectoryPhoneStatus::STATUS_NOT_CALLED;
            $this->phoneCallNotes[$norm] = '';
        }

        foreach ($directory->phoneStatuses as $row) {
            $norm = DirectoryPhoneStatus::normalizePhone($row->phone);
            if (!$norm) continue;
            $this->phoneCallStatuses[$norm] = $row->status ?: DirectoryPhoneStatus::STATUS_NOT_CALLED;
            $this->phoneCallNotes[$norm] = (string)($row->notes ?? '');
        }
    }

    public function updatePhoneCallStatus(string $directoryId, string $phone, string $status): void
    {
        $this->authorize('directory-render');

        $norm = DirectoryPhoneStatus::normalizePhone($phone);
        if (!$norm) return;

        if (!in_array($status, DirectoryPhoneStatus::STATUSES, true)) {
            $status = DirectoryPhoneStatus::STATUS_NOT_CALLED;
        }

        $notes = (string)($this->phoneCallNotes[$norm] ?? '');

        $row = DirectoryPhoneStatus::firstOrNew([
            'directory_id' => $directoryId,
            'phone' => $norm,
        ]);

        $row->status = $status;
        $row->notes = $notes !== '' ? $notes : null;
        $row->last_called_at = now();
        $row->last_called_by = auth()->id();
        $row->save();

        $this->phoneCallStatuses[$norm] = $status;

        $this->dispatch('$refresh');
    }

    public function updatePhoneCallNotes(string $directoryId, string $phone): void
    {
        $norm = DirectoryPhoneStatus::normalizePhone($phone);
        if (!$norm) return;

        $status = (string)($this->phoneCallStatuses[$norm] ?? DirectoryPhoneStatus::STATUS_NOT_CALLED);
        $this->updatePhoneCallStatus($directoryId, $norm, $status);
    }
}