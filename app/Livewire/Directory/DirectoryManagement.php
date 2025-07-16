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
use App\Models\DirectoryRelationship;
use App\Models\PendingTelegramNotification;
use League\Csv\Writer;
use SplTempFileObject;
use App\Models\EventLog;
use Illuminate\Validation\Rule; // Import for unique validation in update

class DirectoryManagement extends Component
{
    use WithPagination, WithFileUploads;

    public $search = '';
    public $perPage = 10;
    public $pageTitle = 'Directory';

    // Current Directory ID for editing
    public $editingDirectoryId;

    // Form fields for ADD (original)
    public $name, $description, $profile_picture;
    public $registration_number, $gst_number;
    public $date_of_birth;
    public $phone, $email, $website;
    public $country_id, $island_id, $property_id;
    public $address, $street_address, $location_type = 'inland';

    public string $registration_label = 'ID Card';
    public ?string $registration_type_id = null;
    public ?string $directory_type_id = null;
    public bool $is_gst_visible = false;
    public bool $is_gender_visible = false;
    public $is_island_visible = false;
    public $is_property_visible = false;
    public string $gender = 'other';
    public string $date_label = 'Date of Birth';

    public bool $has_contact_person = false;
    public ?string $contact_directory_id = null;
    public ?string $contact_designation = null;

    // Form fields for EDIT (prepended with edit_)
    public $edit_name, $edit_description, $edit_profile_picture;
    public $edit_registration_number, $edit_gst_number;
    public $edit_date_of_birth;
    public $edit_phone, $edit_email, $edit_website;
    public $edit_country_id, $edit_island_id, $edit_property_id;
    public $edit_address, $edit_street_address, $edit_location_type = 'inland';

    public string $edit_registration_label = 'ID Card';
    public ?string $edit_registration_type_id = null;
    public ?string $edit_directory_type_id = null;
    public bool $edit_is_gst_visible = false;
    public bool $edit_is_gender_visible = false;
    public $edit_is_island_visible = false;
    public $edit_is_property_visible = false;
    public string $edit_gender = 'other';
    public string $edit_date_label = 'Date of Birth';

    public bool $edit_has_contact_person = false;
    public ?string $edit_contact_directory_id = null;
    public ?string $edit_contact_designation = null;

    public $profile_picture_remove = false;
    public $edit_profile_picture_remove = false;

    public $edit_profile_picture_path;


    public function mount()
    {
        // Default Registration Type: "ID Card" for Add Form
        $defaultRegType = RegistrationType::where('name', 'ID Card')->first();
        $this->registration_type_id = $defaultRegType?->id;
        $this->registration_label = $defaultRegType?->name ?? 'ID Card';

        // Default Directory Type: "Individual" for Add Form
        $defaultDirType = DirectoryType::where('name', 'Individual')->first();
        $this->directory_type_id = $defaultDirType?->id;

        // Apply visibility rule for Add Form
        $this->is_gst_visible = false;

        // Initialize for Edit Form (defaults can be set here or in openEditModal)
        $this->edit_gender = 'male'; // Default for edit if individual
        $this->edit_date_label = 'Date of Birth';
        $this->edit_registration_label = $defaultRegType?->name ?? 'ID Card';
    }

    public function updatingSearch()
    {
        $this->resetPage();
        $this->dispatch('TableUpdated'); 
    }


    public function updatedHasContactPerson($value)
    {
        if (!$value) {
            $this->reset([
                'contact_directory_id',
                'contact_designation',
            ]);
        }
    }

    public function updatedEditHasContactPerson($value)
    {
        if (!$value) {
            $this->reset([
                'edit_contact_directory_id',
                'edit_contact_designation',
            ]);
        }
    }


    public function updatedRegistrationTypeId($value)
    {
        $type = RegistrationType::find($value);
        $this->registration_label = $type?->name ?? 'ID Card';
    }

    public function updatedEditRegistrationTypeId($value)
    {
        $type = RegistrationType::find($value);
        $this->edit_registration_label = $type?->name ?? 'ID Card';
    }

    public function updatedDirectoryTypeId($value)
    {
        $type = DirectoryType::find($value);
        $this->is_gst_visible = strtolower($type?->name) !== 'individual';

        if ($type && strtolower($type->name) !== 'individual') {
            $this->gender = 'other'; // force gender to 'other'
            $this->date_label = 'Registered Date';
            $this->is_gender_visible = false;
        } else {
            $this->date_label = 'Date of Birth';
            $this->gender = 'male';
            $this->is_gender_visible = true;
        }
    }

    public function updatedEditDirectoryTypeId($value)
    {
        $type = DirectoryType::find($value);
        $this->edit_is_gst_visible = strtolower($type?->name) !== 'individual';

        if ($type && strtolower($type->name) !== 'individual') {
            $this->edit_gender = 'other'; // force gender to 'other'
            $this->edit_date_label = 'Registered Date';
            $this->edit_is_gender_visible = false;
        } else {
            $this->edit_date_label = 'Date of Birth';
            $this->edit_gender = 'male';
            $this->edit_is_gender_visible = true;
        }
    }


    public function render()
    {
        $directory = Directory::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('email', 'like', '%' . $this->search . '%')
            ->orWhere('registration_number', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate($this->perPage);

        $individualTypeId = DirectoryType::where('name', 'Individual')->value('id');

        $contacts = Directory::where('status', 'active')
            ->where('directory_type_id', $individualTypeId)
            ->get();

        return view('livewire.directory.directory-management', [
            'directory' => $directory,
            'pageTitle' => $this->pageTitle,
            'contacts' => $contacts,
            'directoryTypes' => DirectoryType::all(),
            'registrationTypes' => RegistrationType::all(),
            'countries' => Country::all(),
            'islands' => Island::all(),
            'properties' => Property::all(),
        ])->layout('layouts.master');
    }

    public function openAddModal()
    {
        $this->resetForm(); // Reset Add form fields
        $this->dispatch('showAddDirectoryModal');
    }

    public function openEdit()
    {
        $this->dispatch('showEditDirectoryModal');
    }

    public function openEditModal($id)
    {
        $this->editingDirectoryId = $id;
        $directory = Directory::with(['type', 'registrationType', 'country', 'island', 'property', 'contactPersonRelationship.linkedDirectory'])->find($id);

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

        // Populate edit form fields
        $this->edit_name = $directory->name;
        $this->edit_description = $directory->description;
        $this->edit_profile_picture_path = $directory->profile_picture;
        // Profile picture is handled differently in the Blade, no direct assignment needed here for initial load
        // The Blade uses @elseif(isset($edit_user) && $edit_user->profile_picture)
        // You might need a $edit_user property if you want to pass the full model,
        // or just ensure the blade correctly references the current profile picture path.
        // For simplicity, we'll assume the blade uses the model directly.
        // If you need to show a temporary preview of a *new* upload, that's handled by $edit_profile_picture.

        $this->edit_directory_type_id = $directory->directory_type_id;
        $this->edit_registration_type_id = $directory->registration_type_id;
        $this->edit_registration_number = $directory->registration_number;
        $this->edit_gst_number = $directory->gst_number;
        $this->edit_gender = $directory->gender;
        $this->edit_date_of_birth = $directory->date_of_birth;
        $this->edit_phone = $directory->phone;
        $this->edit_email = $directory->email;
        $this->edit_website = $directory->website;
        $this->edit_country_id = $directory->country_id;
        $this->edit_island_id = $directory->island_id;
        $this->edit_property_id = $directory->properties_id; // Corrected column name
        $this->edit_address = $directory->address;
        $this->edit_street_address = $directory->street_address;
        $this->edit_location_type = $directory->location_type;

        // Set conditional visibilities based on fetched data
        $this->updatedEditDirectoryTypeId($this->edit_directory_type_id); // Re-run logic for gender/gst visibility
        $this->updatedEditRegistrationTypeId($this->edit_registration_type_id); // Re-run logic for registration label
        $this->updatedEditCountryId($this->edit_country_id); // Re-run logic for island/property visibility
        $this->updatedEditIslandId($this->edit_island_id); // Re-run logic for property visibility and location_type


        // Handle contact person
        $relationship = DirectoryRelationship::where('directory_id', $this->editingDirectoryId)->first();
        if ($relationship) {
            $this->edit_has_contact_person = true;
            $this->edit_contact_directory_id = $relationship->linked_directory_id;
            $this->edit_contact_designation = $relationship->designation;
        } else {
            $this->edit_has_contact_person = false;
        }

        $this->dispatch('reinit-edit-select2');
         
        $this->openEdit();
 
    }


    public function updatedCountryId($value)
    {
        $countryName = Country::find($value)?->name;

        if ($countryName && strtolower($countryName) === 'maldives') {
            $this->is_island_visible = true;
        } else {
            $this->is_island_visible = false;
            $this->island_id = null;
            $this->property_id = null;
        }
    }

    public function updatedEditCountryId($value)
    {
        $countryName = Country::find($value)?->name;

        if ($countryName && strtolower($countryName) === 'maldives') {
            $this->edit_is_island_visible = true;
        } else {
            $this->edit_is_island_visible = false;
            $this->edit_island_id = null;
            $this->edit_property_id = null;
        }
    }


    public function updatedIslandId($value)
    {
        $island = Island::find($value);

        // Update location type
        if ($island && strtolower($island->name) === 'mulah') {
            $this->location_type = 'inland';
        } else {
            $this->location_type = 'outer_islander';
        }

        // Determine if the island has any properties
        $this->is_property_visible = Property::where('island_id', $value)->exists();

        if (!$this->is_property_visible) {
            $this->property_id = null;
        }
    }

    public function updatedEditIslandId($value)
    {
        $island = Island::find($value);

        // Update location type
        if ($island && strtolower($island->name) === 'mulah') {
            $this->edit_location_type = 'inland';
        } else {
            $this->edit_location_type = 'outer_islander';
        }

        // Determine if the island has any properties
        $this->edit_is_property_visible = Property::where('island_id', $value)->exists();

        if (!$this->edit_is_property_visible) {
            $this->edit_property_id = null;
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
            'phone' => 'nullable|string|unique:directories,phone|max:30',
            'email' => 'nullable|email|unique:directories,email|max:255',
            'website' => 'nullable|url|max:255',
            'country_id' => 'nullable|uuid|exists:countries,id',
            'island_id' => 'nullable|uuid|exists:islands,id',
            'property_id' => 'nullable|uuid|exists:properties,id',
            'address' => 'nullable|string|max:255',
            'street_address' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|max:1024',
            'has_contact_person' => 'boolean',
            'contact_directory_id' => Rule::requiredIf($this->has_contact_person) . '|nullable|uuid|exists:directories,id',
            'contact_designation' => 'nullable|string|max:255',
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

        if ($this->has_contact_person) {
            $directoryrelationship = new DirectoryRelationship();
            $directoryrelationship->directory_id = $directory->id;
            $directoryrelationship->linked_directory_id = $this->contact_directory_id;
            $directoryrelationship->link_type = 'linked';
            $directoryrelationship->designation = $this->contact_designation;
            $directoryrelationship->save();

            // ✅ Log Event
            EventLog::create([
                'user_id'         => auth()->id(),
                'event_tab'       => 'DirectoryRelationship',
                'event_entry_id'  => $directoryrelationship->id,
                'event_type'      => 'DirectoryRelationship Created',
                'description'     => 'New Directory Relationship entry created.',
                'event_data'      => $directoryrelationship->toArray(),
                'ip_address'      => request()->ip(),
            ]);
        }

        // ✅ Telegram Notification
        PendingTelegramNotification::create([
            'chat_id' => env('TELEGRAM_GROUP_DIRECTORY'),
            'message_thread_id' => env('TELEGRAM_TOPIC_DIRECTORY'),
            'message' => $this->buildTelegramMessage('Directory Created', $directory),
        ]);

        // ✅ Event Log
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Directory',
            'event_entry_id' => $directory->id,
            'event_type' => 'Directory Created',
            'description' => 'New directory entry created.',
            'event_data' => $directory->toArray(),
            'ip_address' => request()->ip(),
        ]);

        // ✅ SweetAlert Feedback
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

        $this->validate([
            'edit_name' => 'required|string|max:255',
            'edit_directory_type_id' => 'required|uuid|exists:directory_types,id',
            'edit_registration_type_id' => 'nullable|uuid|exists:registration_types,id',
            'edit_country_id' => 'required|uuid|exists:countries,id',
            'edit_registration_number' => [
                'nullable',
                'string',
                Rule::unique('directories', 'registration_number')->ignore($directory->id),
            ],
            'edit_gst_number' => 'nullable|string|max:255',
            'edit_gender' => 'nullable|in:male,female,other',
            'edit_date_of_birth' => 'nullable|date',
            'edit_phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('directories', 'phone')->ignore($directory->id),
            ],
            'edit_email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('directories', 'email')->ignore($directory->id),
            ],
            'edit_website' => 'nullable|url|max:255',
            'edit_island_id' => 'nullable|uuid|exists:islands,id',
            'edit_property_id' => 'nullable|uuid|exists:properties,id',
            'edit_address' => 'nullable|string|max:255',
            'edit_street_address' => 'nullable|string|max:255',
            'edit_profile_picture' => 'nullable|image|max:1024',
            'edit_has_contact_person' => 'boolean',
            'edit_contact_directory_id' => Rule::requiredIf($this->edit_has_contact_person) . '|nullable|uuid|exists:directories,id',
            'edit_contact_designation' => 'nullable|string|max:255',
        ]);

        $directory->name = $this->edit_name;
        $directory->description = $this->edit_description;
        $directory->directory_type_id = $this->edit_directory_type_id;
        $directory->registration_type_id = $this->edit_registration_type_id;
        $directory->registration_number = $this->edit_registration_number;
        $directory->gst_number = $this->edit_gst_number;
        $directory->gender = $this->edit_gender;
        $directory->date_of_birth = $this->edit_date_of_birth;
        $directory->phone = $this->edit_phone;
        $directory->email = $this->edit_email;
        $directory->website = $this->edit_website;
        $directory->country_id = $this->edit_country_id;
        $directory->island_id = $this->edit_island_id;
        $directory->properties_id = $this->edit_property_id; // Corrected column name
        $directory->address = $this->edit_address;
        $directory->street_address = $this->edit_street_address;
        $directory->location_type = $this->edit_location_type;

        // Handle profile picture update
        if ($this->edit_profile_picture) {
            // Delete old picture if exists
            if ($directory->profile_picture) {
                \Storage::disk('public')->delete($directory->profile_picture);
            }
            $directory->profile_picture = $this->edit_profile_picture->store('directory/profiles', 'public');
        } elseif (isset($this->edit_profile_picture_remove) && $this->edit_profile_picture_remove === '1') {
            // Handle explicit removal (if you implemented a 'remove' action in the blade)
            if ($directory->profile_picture) {
                \Storage::disk('public')->delete($directory->profile_picture);
                $directory->profile_picture = null;
            }
        }

        $directory->save();

        // Handle DirectoryRelationship (contact person)
        $relationship = DirectoryRelationship::where('directory_id', $directory->id)->first();

        if ($this->edit_has_contact_person) {
            if ($relationship) {
                // Update existing relationship
                $relationship->linked_directory_id = $this->edit_contact_directory_id;
                $relationship->designation = $this->edit_contact_designation;
                $relationship->save();

                // Log Update Event
                EventLog::create([
                    'user_id'         => auth()->id(),
                    'event_tab'       => 'DirectoryRelationship',
                    'event_entry_id'  => $relationship->id,
                    'event_type'      => 'DirectoryRelationship Updated',
                    'description'     => 'Directory Relationship updated.',
                    'event_data'      => $relationship->toArray(),
                    'ip_address'      => request()->ip(),
                ]);
            } else {
                // Create new relationship
                $directoryrelationship = new DirectoryRelationship();
                $directoryrelationship->directory_id = $directory->id;
                $directoryrelationship->linked_directory_id = $this->edit_contact_directory_id;
                $directoryrelationship->link_type = 'linked';
                $directoryrelationship->designation = $this->edit_contact_designation;
                $directoryrelationship->save();

                // Log Creation Event
                EventLog::create([
                    'user_id'         => auth()->id(),
                    'event_tab'       => 'DirectoryRelationship',
                    'event_entry_id'  => $directoryrelationship->id,
                    'event_type'      => 'DirectoryRelationship Created',
                    'description'     => 'New Directory Relationship entry created.',
                    'event_data'      => $directoryrelationship->toArray(),
                    'ip_address'      => request()->ip(),
                ]);
            }
        } elseif ($relationship) {
            // If has_contact_person is false but a relationship exists, delete it
            $relationship->delete();

            // Log Deletion Event
            EventLog::create([
                'user_id'         => auth()->id(),
                'event_tab'       => 'DirectoryRelationship',
                'event_entry_id'  => $relationship->id,
                'event_type'      => 'DirectoryRelationship Deleted',
                'description'     => 'Directory Relationship deleted.',
                'event_data'      => $relationship->toArray(),
                'ip_address'      => request()->ip(),
            ]);
        }

        // ✅ Telegram Notification for update
        PendingTelegramNotification::create([
            'chat_id' => env('TELEGRAM_GROUP_DIRECTORY'),
            'message_thread_id' => env('TELEGRAM_TOPIC_DIRECTORY'),
            'message' => $this->buildTelegramMessage('Directory Updated', $directory),
        ]);

        // ✅ Event Log for update
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Directory',
            'event_entry_id' => $directory->id,
            'event_type' => 'Directory Updated',
            'description' => 'Directory entry updated.',
            'event_data' => $directory->toArray(),
            'ip_address' => request()->ip(),
        ]);

        // ✅ SweetAlert Feedback
        $this->dispatch('swal', [
            'title' => 'Updated',
            'text' => 'Directory updated successfully.',
            'icon' => 'success',
            'confirmButtonText' => 'Ok!',
            'confirmButton' => 'btn btn-primary'
        ]);

        session()->flash('success', 'Directory updated successfully.');
        $this->resetEditForm(); // Reset Edit form fields
        $this->dispatch('closeEditDirectoryModal');
    }

    protected function buildTelegramMessage($event, $directory)
    {
        $envLabel = app()->environment('production') ? '🟢 Production' : '🧪 Development';

        return "<b>📁 Directory {$event}</b>\n" .
            "<i>{$envLabel} Environment</i>\n" .
            "──────────────────────────────\n" .
            "<b>🏷️ Name:</b> {$directory->name}\n" .
            "<b>🧾 Reg No:</b> " . ($directory->registration_number ?? 'N/A') . "\n" .
            "<b>💼 Type:</b> " . optional($directory->type)->name . "\n" .
            "<b>🌐 Website:</b> " . ($directory->website ?? 'N/A') . "\n" .
            "<b>📧 Email:</b> " . ($directory->email ?? 'N/A') . "\n" .
            "<b>📞 Phone:</b> " . ($directory->phone ?? 'N/A') . "\n" .
            "<b>🏝️ Island:</b> " . optional($directory->island)->name . "\n" .
            "<b>📍 Address:</b> " . ($directory->street_address ?? 'N/A') . "\n" .
            "<b>🧾 GST:</b> " . ($directory->gst_number ?? 'N/A') . "\n" .
            "──────────────────────────────\n" .
            "<b>👤 By:</b> " . auth()->user()->name . "\n" .
            "<b>🕒 At:</b> " . now()->format('d M Y H:i');
    }


    public function resetForm() // For Add Modal
    {
        $this->reset([
            'name', 'description', 'profile_picture',
            'directory_type_id', 'registration_type_id', 'registration_number', 'gst_number',
            'gender', 'date_of_birth',
            'phone', 'email', 'website',
            'country_id', 'island_id', 'address', 'street_address',
            'property_id', 'location_type',
            'has_contact_person', 'contact_directory_id', 'contact_designation',
        ]);
        $this->profile_picture = null; // Ensure file input is cleared
        // Reapply defaults for Add form after reset
        $defaultRegType = RegistrationType::where('name', 'ID Card')->first();
        $this->registration_type_id = $defaultRegType?->id;
        $this->registration_label = $defaultRegType?->name ?? 'ID Card';
        $defaultDirType = DirectoryType::where('name', 'Individual')->first();
        $this->directory_type_id = $defaultDirType?->id;
        $this->is_gst_visible = false;
        $this->gender = 'male';
        $this->date_label = 'Date of Birth';
        $this->is_gender_visible = true;
        $this->is_island_visible = false;
        $this->is_property_visible = false;
        $this->location_type = 'inland';
        $this->has_contact_person = false;

        $this->dispatch('formSubmittedOrReset'); // Event for Add modal
    }

    public function resetEditForm() // For Edit Modal
    {
        $this->reset([
            'editingDirectoryId',
            'edit_name', 'edit_description', 'edit_profile_picture',
            'edit_directory_type_id', 'edit_registration_type_id', 'edit_registration_number', 'edit_gst_number',
            'edit_gender', 'edit_date_of_birth',
            'edit_phone', 'edit_email', 'edit_website',
            'edit_country_id', 'edit_island_id', 'edit_address', 'edit_street_address',
            'edit_property_id', 'edit_location_type',
            'edit_has_contact_person', 'edit_contact_directory_id', 'edit_contact_designation',
        ]);
        $this->edit_profile_picture = null; // Ensure file input is cleared
        $this->dispatch('editFormSubmittedOrReset'); // Event for Edit modal
    }
}