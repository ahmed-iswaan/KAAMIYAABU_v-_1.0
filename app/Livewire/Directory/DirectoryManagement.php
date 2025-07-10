<?php

namespace App\Livewire\Directory;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Support\Str;
use App\Models\Directory;
use App\Models\DirectoryType;
use App\Models\RegistrationType;
use App\Models\Country;
use App\Models\Island;
use App\Models\Property;

class DirectoryManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $pageTitle = 'Directory';

    // Form fields
    public $name, $description, $profile_picture;
    public $directory_type_id, $registration_type_id, $registration_number, $gst_number;
    public $gender, $date_of_birth, $death_date;
    public $contact_person, $phone, $email, $website;
    public $country_id, $island_id, $property_id;
    public $address, $street_address, $location_type = 'inland';

    public function render()
    {
        $directory = Directory::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhere('registration_number', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.directory.directory-management', [
            'directory' => $directory,
            'pageTitle' => $this->pageTitle,
            'directoryTypes' => DirectoryType::all(),
            'registrationTypes' => RegistrationType::all(),
            'countries' => Country::all(),
            'islands' => Island::all(),
            'properties' => Property::all(),
        ])->layout('layouts.master');
    }

    public function updatedIslandId($value)
    {
        // Normalize case just in case
        $islandName = Island::find($value)?->name;

        if ($islandName && strtolower($islandName) === 'mulah') {
            $this->location_type = 'inland';
        } else {
            $this->location_type = 'outer_islander';
        }
    }


    public function save()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'directory_type_id' => 'required|uuid|exists:directory_types,id',
            'registration_type_id' => 'nullable|uuid|exists:registration_types,id',
            'registration_number' => 'nullable|string|unique:directories,registration_number',
            'gst_number' => 'nullable|string|max:255',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'nullable|date',
            'death_date' => 'nullable|date|after_or_equal:date_of_birth',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'country_id' => 'nullable|uuid|exists:countries,id',
            'island_id' => 'nullable|uuid|exists:islands,id',
            'property_id' => 'nullable|uuid|exists:properties,id',
            'address' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|max:1024',
        ]);

        $directory = new Directory();
        $directory->id = Str::uuid();
        $directory->name = $this->name;
        $directory->description = $this->description;
        $directory->directory_type_id = $this->directory_type_id;
        $directory->registration_type_id = $this->registration_type_id;
        $directory->registration_number = $this->registration_number;
        $directory->gst_number = $this->gst_number;
        $directory->gender = $this->gender;
        $directory->date_of_birth = $this->date_of_birth;
        $directory->death_date = $this->death_date;
        $directory->contact_person = $this->contact_person;
        $directory->phone = $this->phone;
        $directory->email = $this->email;
        $directory->website = $this->website;
        $directory->country_id = $this->country_id;
        $directory->island_id = $this->island_id;
        $directory->properties_id = $this->property_id;
        $directory->address = $this->address;
        $directory->street_address = $this->street_address;
        $directory->location_type = $this->location_type;

        if ($this->profile_picture) {
            $directory->profile_picture = $this->profile_picture->store('directory/profiles', 'public');
        }

        $directory->save();

        session()->flash('success', 'Directory added successfully.');
        $this->resetForm();
        $this->dispatch('closeAddModal');
    }

    public function resetForm()
    {
        $this->reset([
            'name', 'description', 'profile_picture',
            'directory_type_id', 'registration_type_id', 'registration_number', 'gst_number',
            'gender', 'date_of_birth', 'death_date',
            'contact_person', 'phone', 'email', 'website',
            'country_id', 'island_id', 'address', 'street_address',
            'property_id', 'location_type',
            
        ]);
    }
}
