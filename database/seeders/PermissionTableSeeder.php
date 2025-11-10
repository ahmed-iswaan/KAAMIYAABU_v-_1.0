<?php

namespace Database\Seeders;
  
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
  
class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
           'role-render',
           'role-createRole',
           'role-editRole',
           'role-updateRole',
           'role-deleteRole',
           'role-exportRoles',
           'user-render',
           'user-export',
           'user-exportToCSV',
           'user-createUser',
           'user-editUser',
           'user-updateUser',
           'user-removeRole',
           'user-RemoveUserRole',
           'agent-render',
           'voters-render',
           'directory-render',
           'task-render',
           'property-render',
           'log-render',
           'election-render',
           'formslist-render',
           'dashboard-render',
           'requests-voters-render'
        ];
        
        foreach ($permissions as $permission) {
             Permission::create(['name' => $permission]);
        }
    }
}