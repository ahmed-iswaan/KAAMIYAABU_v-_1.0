<?php

namespace App\Livewire\Roles;

use Spatie\Permission\Models\Role;
use League\Csv\Writer;
use SplTempFileObject;
use Livewire\WithPagination;
use Livewire\Component;
use App\Models\EventLog;
use Spatie\Permission\Models\Permission;
use DB;
use App\Services\DhiraaguSmsService;

use App\Models\PendingTelegramNotification;


class RolesManagement extends Component
{
    use WithPagination;

    public $perPage = 6;
    public $search = '';
    public $pageTitle = 'Roles';
    public $role_name;
    public $role_description;
    public $selectedPermission = [];

    public $editroleId;
    public $editrole_name;
    public $editrole_description;
    public $editselectedPermissions = [];

    public $editname;
    public $editid;

    protected $rules = [
        'role_name' => 'required|string|unique:roles,name',
        'role_description' => 'nullable|string|max:255', // Validation for role details
        'selectedPermission' => 'required|array|min:1',
    ];

    public function updatingSearch()
    {
        $this->resetPage(); // Reset pagination when search query changes
    }

    public function render()
    {
        $roles = Role::with('permissions')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orWhere('details', 'like', '%' . $this->search . '%') // Search in details column
            ->orWhereHas('permissions', function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('id', 'DESC')
            ->paginate($this->perPage);

        $permission = Permission::get();


        return view('livewire.roles.roles-management', [
            'roles' => $roles,
            'permission' => $permission,
        ])->layout('layouts.master');
    }

    public function exportRoles()
    {
        // Get all roles with their permissions
        $roles = Role::with('permissions')->get();

        // Generate CSV
        return $this->exportRolesToCSV($roles);
    }

    public function exportRolesToCSV($roles)
    {
        // Create a temporary file
        $csv = Writer::createFromFileObject(new SplTempFileObject());
        $csv->insertOne(['Role Name', 'Details', 'Permissions']); // Insert header row

        // Insert role data
        foreach ($roles as $role) {
            $csv->insertOne([
                $role->name,
                $role->details ?? 'N/A', // Include role details
                $role->permissions->pluck('name')->join(', ') // Convert permissions to a string
            ]);
        }

        // Get the CSV content as a string
        $csvContent = (string) $csv;

        // Create a temporary file to store the CSV content
        $tempFile = tempnam(sys_get_temp_dir(), 'roles_');
        file_put_contents($tempFile, $csvContent);

        // Log the export event
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Role',
            'event_entry_id' => auth()->id(),
            'event_type' => 'Export Roles List Completed',
            'description' => 'The user successfully exported the roles list.',
            'event_data' => [
                'export_completed_at' => now(),
                'description' => 'Successfully exported the roles list.',
                'number_of_roles' => $roles->count(),
                'export_file_size' => filesize($tempFile), // Optional: Log the file size
            ],
            'ip_address' => request()->ip(),
        ]);

        // Show success message
        $this->dispatch('swal', [
            'title' => 'Roles List Exported',
            'text' => 'The roles list has been exported successfully.',
            'icon' => 'success',
            'buttonsStyling' => false,
            'confirmButtonText' => 'Ok, got it!',
            'confirmButton' => 'btn btn-primary',
        ]);

        $this->dispatch('closeModal');

        // Return the file as a download response
        return response()->download($tempFile, 'RolesList.csv')->deleteFileAfterSend(true);
    }

    public function createRole()
    {
        // Log role creation start event
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Roles',
            'event_entry_id' => auth()->id(),
            'event_type' => 'Role Creation Started',
            'description' => 'The role creation process has started.',
            'event_data' => [
                'role_name' => $this->role_name,
                'role_description' => $this->role_description, // Log description
                'selected_permissions' => $this->selectedPermission,
            ],
            'ip_address' => request()->ip(),
        ]);

        if ($this->validate()) {
            try {
                // Create a new role with description (details)
                $role = Role::create([
                    'name' => $this->role_name,
                    'details' => $this->role_description, // Storing role description
                ]);

                // Assign selected permissions
                if (!empty($this->selectedPermission)) {
                    $permissions = Permission::whereIn('id', $this->selectedPermission)->get();
                    $role->syncPermissions($permissions);
                }

                // Log role creation success
                EventLog::create([
                    'user_id' => auth()->id(),
                    'event_tab' => 'Roles',
                    'event_entry_id' => $role->id,
                    'event_type' => 'Role Created Successfully',
                    'description' => 'A new role was created successfully.',
                    'event_data' => [
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'role_description' => $role->details, // Log description
                        'assigned_permissions' => $role->permissions->pluck('name')->toArray(),
                    ],
                    'ip_address' => request()->ip(),
                ]);

                $creatorName = auth()->user()->name ?? 'Unknown User';
                $msg = 
                    "<b>📢 New Role Created Successfully</b>\n\n" .
                    "<b>Role Name:</b> {$role->name}\n" .
                    "<b>Description:</b> " . ($role->details ?? 'N/A') . "\n" .
                    "<b>Permissions Assigned:</b> " 
                        . $role->permissions->pluck('name')->join(', ') . "\n" .
                    "<b>Created By:</b> {$creatorName}\n" .
                    "<b>Created At:</b> " . now()->format('d M Y H:i');

                // 2) Enqueue it—don’t call sendMessage() here
                PendingTelegramNotification::create([
                    'chat_id' => env('TELEGRAM_ADMIN_CHAT_ID'),
                    'message' => $msg,
                ]);
                app(DhiraaguSmsService::class)->queue('9609996759', 'Favara Transfer to your account 90403***01000 for MVR 100000000.00 was processed on 18/06/2025 16:46:53. Ref. no. 703567895-742683729');
                // Reset fields
                $this->reset(['role_name', 'role_description', 'selectedPermission']);

                // Success message
                session()->flash('message', 'Role created successfully!');

                // Close modal
                $this->dispatch('closeModal');

                $this->dispatch('swal', [
                    'title' => 'Role Added',
                    'text' => 'Role created successfully.',
                    'icon' => 'success',
                    'buttonsStyling' => false,
                    'confirmButtonText' => 'Ok, got it!',
                    'confirmButton' => 'btn btn-primary',
                ]);

            } catch (\Exception $e) {
                // Log error in EventLog
                EventLog::create([
                    'user_id' => auth()->id(),
                    'event_tab' => 'Roles',
                    'event_entry_id' => auth()->id(),
                    'event_type' => 'Role Creation Failed',
                    'description' => 'Role creation process failed.',
                    'event_data' => [
                        'error_message' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString(),
                    ],
                    'ip_address' => request()->ip(),
                ]);

                session()->flash('error', 'An error occurred while creating the role.');
                $this->dispatch('showModal');
            }
        } else {
            // Log validation errors
            EventLog::create([
                'user_id' => auth()->id(),
                'event_tab' => 'Roles',
                'event_entry_id' => auth()->id(),
                'event_type' => 'Role Creation Validation Failed',
                'description' => 'Role creation failed due to validation errors.',
                'event_data' => [
                    'validation_errors' => $this->getErrorBag()->toArray(),
                ],
                'ip_address' => request()->ip(),
            ]);

            $this->dispatch('showModal');
        }
    }


    // Edit role details
    public function editRole($id)
    {
        // Log the start of editing
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Roles',
            'event_entry_id' => $id,
            'event_type' => 'Edit Role Started',
            'description' => 'Started editing role details.',
            'event_data' => [
                'role_id' => $id,
            ],
            'ip_address' => request()->ip(),
        ]);

        // Retrieve role by ID
        $role = Role::find($id);

        if ($role) {
            // Populate the properties with role details
            $this->editroleId = $role->id;
            $this->editrole_name = $role->name;
            $this->editrole_description = $role->details; // Assuming 'details' column exists
            $this->editselectedPermissions = $role->permissions->pluck('id')->toArray(); // Get assigned permissions
        }

        // dd($this->editselectedPermissions);

        // Open the edit role modal
        $this->dispatch('showModalEditRole');
    }

    // Update role details
    public function updateRole()
    {
        // Log role update initiation
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Roles',
            'event_entry_id' => $this->editroleId,
            'event_type' => 'Role Update Started',
            'description' => 'Started updating role details.',
            'event_data' => [
                'role_id' => $this->editroleId,
                'new_name' => $this->editrole_name,
                'new_description' => $this->editrole_description,
                'new_permissions' => $this->editselectedPermissions,
            ],
            'ip_address' => request()->ip(),
        ]);

        // Validate input
        $this->validate([
            'editrole_name' => 'required|string|unique:roles,name,' . $this->editroleId,
            'editrole_description' => 'nullable|string|max:255',
            'editselectedPermissions' => 'required|array',
        ]);

        // Find role by ID
        $role = Role::find($this->editroleId);

        if ($role) {
            // Update role details
            $role->update([
                'name' => $this->editrole_name,
                'details' => $this->editrole_description,
            ]);

            // Remove all existing permissions
            DB::table('role_has_permissions')->where('role_id', $this->editroleId)->delete();


             // Assign selected permissions
                if (!empty($this->editselectedPermissions)) {
                    $permissions = Permission::whereIn('id', $this->editselectedPermissions)->get();
                    $role->syncPermissions($permissions);
                }

            // Log success
            EventLog::create([
                'user_id' => auth()->id(),
                'event_tab' => 'Roles',
                'event_entry_id' => $role->id,
                'event_type' => 'Role Updated Successfully',
                'description' => 'Role updated successfully.',
                'event_data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'role_description' => $role->details,
                    'assigned_permissions' => $role->permissions->pluck('name')->toArray(),
                ],
                'ip_address' => request()->ip(),
            ]);

            // Success message
            session()->flash('message', 'Role updated successfully!');

            // Close modal
            $this->dispatch('closeModalEditRole');

            // Success alert
            $this->dispatch('swal', [
                'title' => 'Role Update',
                'text' => 'Role updated successfully.',
                'icon' => 'success',
                'buttonsStyling' => false,
                'confirmButtonText' => 'Ok, got it!',
                'confirmButton' => 'btn btn-primary',
            ]);
        } else {
            // Log failure
            EventLog::create([
                'user_id' => auth()->id(),
                'event_tab' => 'Roles',
                'event_entry_id' => $this->editroleId,
                'event_type' => 'Role Not Found',
                'description' => 'Role not found for the provided ID.',
                'event_data' => [
                    'role_id' => $this->editroleId,
                ],
                'ip_address' => request()->ip(),
            ]);

            // Flash error
            session()->flash('error', 'Role not found!');
        }
    }

    public function removeRole($id)
    {
        $role = DB::table('roles')->where('id', $id)->first();

        if ($role) {
            $this->editname = $role->name;
            $this->editid = $role->id;
        }

        // Log the role deletion attempt
        EventLog::create([
            'user_id' => auth()->id(),
            'event_tab' => 'Role',
            'event_entry_id' => $id,
            'event_type' => 'Role Deletion Started',
            'description' => 'Started deleting role.',
            'event_data' => ['role_id' => $id, 'role_name' => $role->name ?? 'Unknown'],
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
            'event_tab' => 'Role',
            'event_entry_id' =>  $this->editid,
            'event_type' => 'Role Deletion Started',
            'description' => 'Started deleting role.',
            'event_data' => [
                'role_id' => $this->editid,
                'name' => $this->editname,
            ],
            'ip_address' => request()->ip(),
        ]);


          // Find the role by ID
         $role = DB::table('roles')->where('id', $this->editid)->first();


        // Check if the user exists
        if ($role) {

            // Delete the role
            DB::table("roles")->where('id', $this->editid)->delete();

            // Assign new roles to the user

            EventLog::create([
                'user_id' => auth()->id(),
                'event_tab' => 'Role',
                'event_entry_id' => $this->editid,
                'event_type' => 'Role Deleted Successfully',
                'description' => 'Role deleted successfully.',
                'event_data' => ['role_id' => $this->editid, 'role_name' => $role->name ?? 'Unknown'],
                'ip_address' => request()->ip(),
            ]);

            // Flash a success message to the session
            session()->flash('message', 'Role Deleted Successfully!');

            // Close the modal after success
            $this->dispatch('closeModalremove');

            // Dispatch success alert
            $this->dispatch('swal', [
                'title' => 'Role Deleted Successfully',
                'text' => 'Role Deleted Successfully',
                'icon' => 'success',
                'buttonsStyling' => false,
                'confirmButtonText' => 'Ok, got it!',
                'confirmButton' => 'btn btn-primary',
            ]);
        } else {

             // Log the failure if user is not found
            EventLog::create([
                'user_id' => auth()->id(),
                'event_tab' => 'Role',
                'event_entry_id' =>  $this->editid,
                'event_type' => 'Role Not Found',
                'description' => 'Attempted to delete a role that does not exist.',
                'event_data' => [
                    'user_id' => $this->editid,
                ],
                'ip_address' => request()->ip(),
            ]);

            // Flash error message if user is not found
            session()->flash('error', 'Role Not Found!');
        }
    }

}
