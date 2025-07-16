<?php
namespace App\Livewire\User;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads; 
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use League\Csv\Writer;
use SplTempFileObject;
use App\Models\EventLog;
use Illuminate\Support\Facades\Storage; 

class UserManagement extends Component
{
    use WithPagination,WithFileUploads;


    public $name, $email,$profile_picture,$job_title, $password, $roles, $userId, $selectedRoles = [];
    public $updateMode = false;
    public $showModal = false;
    public $staff_id;
    public $perPage = 6;
    public $search = '';
    public $pageTitle = 'Users';
    public $password_confirmation;
    public $profile_picture_remove = false;



    public $edituserId, $editname, $editlastlogin, $editemail,$editstaff_id, $editjob_title, $editselectedRoles ,$edit_profile_picture;

    public $edit_profile_picture_path;         // existing DB value


    protected $rules = [
        'name' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6',
        'selectedRoles' => 'required'
    ];

    protected $listeners = ['openEditModal'];



    public function exportUsers()
    {

        // Get the users based on selected role
        $users = User::all();

        // Generate CSV
        return $this->exportToCSV($users);
    }

    public function exportToCSV($users)
    {


        // Create a temporary file
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne(['Name', 'Staff ID', 'Job Title', 'Email', 'Role']); // Insert header row

        // Insert user data
        foreach ($users as $user) {
            $csv->insertOne([$user->name,$user->staff_id,$user->job_title, $user->email, $user->roles->pluck('name')->join(', ')]);
        }

        // Get the CSV content as a string
        $csvContent = (string) $csv;


        // Create a temporary file to store the CSV content
        $tempFile = tempnam(sys_get_temp_dir(), 'users_');
        file_put_contents($tempFile, $csvContent);

        // Log the completion of the export action
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'User',
            'event_entry_id' => auth()->id(),
            'event_type' => 'Export Users List Completed',
            'description' => 'The user successfully exported the users list.',
            'event_data' => [
                'export_completed_at' => now(),
                'description' => 'successfully exported the users list.',
                'number_of_users' => $users->count(),
                'export_file_size' => filesize($tempFile), // Optional: Log the file size
            ],
            'ip_address' => request()->ip(),
        ]);

        $this->dispatch('swal', [
            'title' => 'Users List Exported',
            'text' => 'The users list has been exported successfully.',
            'icon' => 'success',
            'buttonsStyling' => false,
            'confirmButtonText' => 'Ok, got it!',
            'confirmButton' => 'btn btn-primary',
        ]);

        $this->dispatch('closeModal');

        // Return the file as a download response
        return response()->download($tempFile, 'UsersList.csv')->deleteFileAfterSend(true);

    }


    public function updatingSearch()
    {
        $this->resetPage(); // Reset pagination when search changes
        $this->dispatch('TableUpdated'); 
    }

    public function rules()
    {
        return [
            'staff_id' => 'required|string|unique:users,employee_id',
            'selectedRoles' => 'required|array|min:1',
        ];
    }

    public function render()
    {
        $users = User::where('name', 'like', '%' . $this->search . '%')
        ->orWhere('email', 'like', '%' . $this->search . '%')
        ->orWhere('staff_id', 'like', '%' . $this->search . '%')
        ->latest()
        ->paginate($this->perPage);

        $roles_list = Role::select('name', 'details')->get();  // Get roles from DB

        return view('livewire.user.user-management', [
            'users' => $users,
            'roles_list' => $roles_list,
            'pageTitle' => $this->pageTitle,
        ])->layout('layouts.master');
    }

public function createUser()
{
    // 1) Log the start of the creation process
    EventLog::create([
        'user_id'        => auth()->id(),
        'event_tab'      => 'User',
        'event_type'     => 'User Creation Started',
        'description'    => 'Starting creation of user: ' . $this->name,
        'event_data'     => [
            'name'       => $this->name,
            'email'      => $this->email,
            'staff_id'   => $this->staff_id,
            'job_title'  => $this->job_title,
            'roles'      => $this->selectedRoles,
        ],
        'ip_address'     => request()->ip(),
    ]);

    // 2) Validate all form inputs (including the file upload)
    $validated = $this->validate([
        'name'             => 'required|string|max:255',
        'email'            => 'required|email|unique:users,email',
        'password'              => 'required|min:6|confirmed',
        'password_confirmation' => 'required_with:password|min:6',
        'staff_id'         => 'required|string|unique:users,staff_id',
        'job_title'        => 'required|string|max:255',
        'profile_picture'  => 'nullable|image|max:1024',  // max 1MB
        'selectedRoles'    => 'required|array|min:1',
    ]);

    try {
        // 3) Handle profile picture upload (if present)
        $path = null;
        if ($this->profile_picture) {
            $path = $this->profile_picture->store('profile_pictures', 'public');
        }

        // 4) Create the user
        $user = User::create([
            'name'             => $validated['name'],
            'email'            => $validated['email'],
            'password'         => Hash::make($validated['password']),
            'staff_id'         => $validated['staff_id'],
            'job_title'        => $validated['job_title'],
            'profile_picture'  => $path,
        ]);

        // 5) Assign the selected roles
        $user->assignRole($validated['selectedRoles']);

        // 6) Log success
        EventLog::create([
            'user_id'        => auth()->id(),
            'event_tab'      => 'User',
            'event_type'     => 'User Created Successfully',
            'event_entry_id'=> $user->id,
            'description'    => 'User created: ' . $user->name,
            'event_data'     => [
                'user_id'   => $user->id,
                'staff_id'  => $user->staff_id,
                'roles'     => $validated['selectedRoles'],
            ],
            'ip_address'     => request()->ip(),
        ]);

        // 7) Feedback & reset
        session()->flash('message', 'User created successfully!');
        $this->resetFields();  // make sure this also clears name, email, password, job_title, profile_picture, selectedRoles
        $this->dispatch('closeModal');
        $this->dispatch('swal', [
        'title'             => 'Success',
        'text'              => 'User added.',
        'icon'              => 'success',
        'buttonsStyling'    => false,
        'confirmButtonText' => 'Ok!',
        'confirmButton'     => 'btn btn-primary',
        ]);

    } catch (\Exception $e) {
        // 8) Log failure
        EventLog::create([
            'user_id'        => auth()->id(),
            'event_tab'      => 'User',
            'event_type'     => 'User Creation Failed',
            'description'    => 'Error: ' . $e->getMessage(),
            'event_data'     => ['stack' => $e->getTraceAsString()],
            'ip_address'     => request()->ip(),
        ]);

        session()->flash('error', 'Failed to create user.');
        $this->dispatch('showModal');
    }
}


    public function resetFields()
    {
        $this->name                   = '';
        $this->email                  = '';
        $this->password               = '';
        $this->password_confirmation  = '';
        $this->staff_id               = '';
        $this->job_title              = '';
        $this->profile_picture        = null;
        $this->selectedRoles          = [];
    }


    public function editUser($id)
    {

         // Log the start of the editing process
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'User',
            'event_entry_id' => $id,
            'event_type' => 'Edit User Started',
            'description' => 'Started editing user details.',
            'event_data' => [
                'user_id' => $id,
                'name' => $this->editname,
                'email' => $this->editemail,
                'staff_id' => $this->editstaff_id,
                'new_roles' => $this->editselectedRoles,
            ],
            'ip_address' => request()->ip(),
        ]);

        // Retrieve user details by ID
        $user = User::find($id);

        if ($user) {
            // Populate the public properties with the user's details
            $this->edituserId = $user->id;
            $this->edit_profile_picture_path = $user->profile_picture;
            $this->edit_profile_picture      = null;
            $this->editstaff_id = $user->staff_id;
            $this->editname = $user->name;
            $this->editemail = $user->email;
            $this->editjob_title = $user->job_title;
            $this->editlastlogin = $user->last_login_at;
            $this->editselectedRoles = $user->roles->pluck('name')->toArray(); // Assuming the user has roles
        }

        // Open the modal (Optional, if you're controlling visibility from Livewire)
        $this->dispatch('showModaledit');
    }

   public function updateUser()
{
    // 1) Log start
    EventLog::create([
        'user_id'        => auth()->id(),
        'event_tab'      => 'User',
        'event_entry_id'=> $this->edituserId,
        'event_type'     => 'User Update Started',
        'description'    => 'Updating user ID ' . $this->edituserId,
        'event_data'     => [
            'new_name'        => $this->editname,
            'new_email'       => $this->editemail,
            'new_staff_id'    => $this->editstaff_id,
            'new_job_title'   => $this->editjob_title,
            'new_roles'       => $this->editselectedRoles,
        ],
        'ip_address'     => request()->ip(),
    ]);

    // 2) Validation
    $validated = $this->validate([
        'editname'            => 'required|string|max:255',
        'editemail'           => 'required|email|unique:users,email,' . $this->edituserId,
        'editstaff_id'        => 'required|string|unique:users,staff_id,' . $this->edituserId,
        'editjob_title'       => 'required|string|max:255',
        'edit_profile_picture' => 'nullable|image|max:1024', // optional new avatar
        'editselectedRoles'   => 'required|array|min:1',
    ]);

    // 3) Find model
    $user = User::find($this->edituserId);

    if (! $user) {
        // Log not-found
        EventLog::create([
            'user_id'        => auth()->id(),
            'event_tab'      => 'User',
            'event_entry_id'=> $this->edituserId,
            'event_type'     => 'User Not Found',
            'description'    => 'Edit failed: user not found.',
            'ip_address'     => request()->ip(),
        ]);
        session()->flash('error','User not found!');
        return;
    }


    if ($this->edit_profile_picture) {
        if ($user->profile_picture) {
            Storage::disk('public')->delete($user->profile_picture);
        }
        $user->profile_picture = $this->edit_profile_picture->store('profile_pictures', 'public');
    }

    // 5) Update fields
    $user->name      = $validated['editname'];
    $user->email     = $validated['editemail'];
    $user->staff_id  = $validated['editstaff_id'];
    $user->job_title = $validated['editjob_title'];
    $user->save();

    // 6) Sync roles
    $user->syncRoles($validated['editselectedRoles']);

    // 7) Log success
    EventLog::create([
        'user_id'        => auth()->id(),
        'event_tab'      => 'User',
        'event_entry_id'=> $user->id,
        'event_type'     => 'User Updated Successfully',
        'description'    => 'User updated.',
        'event_data'     => [
            'user_id'      => $user->id,
            'staff_id'     => $user->staff_id,
            'job_title'    => $user->job_title,
            'roles'        => $validated['editselectedRoles'],
        ],
        'ip_address'     => request()->ip(),
    ]);

    // 8) Feedback & cleanup
    session()->flash('message','User updated successfully!');
    $this->dispatch('closeModaledit');
    $this->dispatch('swal', [
        'title'             => 'Updated',
        'text'              => 'User details updated.',
        'icon'              => 'success',
        'buttonsStyling'    => false,
        'confirmButtonText' => 'Ok, got it!',
        'confirmButton'     => 'btn btn-primary',
    ]);
}



    public function removeRole($id)
    {



        // Retrieve user details by ID
        $user = User::find($id);

        if ($user) {
            // Populate the public properties with the user's details
            $this->edituserId = $user->id;
            $this->editstaff_id = $user->staff_id;
            $this->editname = $user->name;
            $this->editemail = $user->email;
            $this->editjob_title = $user->job_title;
            $this->editlastlogin = $user->last_login_at;
            $this->editselectedRoles = $user->roles->pluck('name')->toArray(); // Assuming the user has roles
        }

         // Log the start of the role removal process
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'User',
            'event_entry_id' =>  $id,
            'event_type' => 'Remove User Role Started',
            'description' => 'Started removing role from user.',
            'event_data' => [
                'user_id' => $id,
                'name' => $this->editname,
                'email' => $this->editemail,
                'staff_id' => $this->editstaff_id,
            ],
            'ip_address' => request()->ip(),
        ]);

        // Open the modal (Optional, if you're controlling visibility from Livewire)
        $this->dispatch('showModalremove');
    }

    public function RemoveUserRole()
    {

        // Log the start of the role removal process
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'User',
            'event_entry_id' =>  $this->edituserId,
            'event_type' => 'User Role Removal Started',
            'description' => 'Started removing user role.',
            'event_data' => [
                'user_id' => $this->edituserId,
                'name' => $this->editname,
                'email' => $this->editemail,
                'staff_id' => $this->editstaff_id,
            ],
            'ip_address' => request()->ip(),
        ]);

        // Find the user by ID
        $user = User::find($this->edituserId);

        // Check if the user exists
        if ($user) {
            // Remove the existing roles first (from the model_has_roles table)
            DB::table('model_has_roles')->where('model_id', $this->edituserId)->delete();

            // Assign new roles to the user

             // Log the successful role removal event
            EventLog::create([
                'user_id' => auth()->id(),
                'event_tab' => 'User',
                'event_entry_id' =>  $user->id,
                'event_type' => 'User Role Removed Successfully',
                'description' => 'User role removed successfully.',
                'event_data' => [
                    'user_id' => $user->id,
                    'staff_id' => $user->staff_id,
                    'name' => $this->editname,
                    'email' => $this->editemail,
                    'roles_removed' => $this->editselectedRoles,
                ],
                'ip_address' => request()->ip(),
            ]);

            // Flash a success message to the session
            session()->flash('message', 'User updated successfully!');

            // Close the modal after success
            $this->dispatch('closeModalremove');

            // Dispatch success alert
            $this->dispatch('swal', [
                'title' => 'User Role Removed',
                'text' => 'User Role Removed successfully.',
                'icon' => 'success',
                'buttonsStyling' => false,
                'confirmButtonText' => 'Ok, got it!',
                'confirmButton' => 'btn btn-primary',
            ]);
        } else {

             // Log the failure if user is not found
            EventLog::create([
                'user_id' => auth()->id(),
                'event_tab' => 'User',
                'event_entry_id' =>  $this->edituserId,
                'event_type' => 'User Not Found',
                'description' => 'User not found for the provided ID.',
                'event_data' => [
                    'user_id' => $this->edituserId,
                ],
                'ip_address' => request()->ip(),
            ]);

            // Flash error message if user is not found
            session()->flash('error', 'User not found!');
        }
    }



}
