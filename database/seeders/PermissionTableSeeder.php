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
        //    'role-render',
        //    'role-createRole',
        //    'role-editRole',
        //    'role-updateRole',
        //    'role-deleteRole',
        //    'role-exportRoles',
        //    'user-render',
        //    'user-export',
        //    'user-exportToCSV',
        //    'user-createUser',
        //    'user-editUser',
        //    'user-updateUser',
        //    'user-removeRole',
        //    'user-RemoveUserRole',
        //    'agent-render',
        //    'voters-render',
        //    'directory-render',
        //    'task-render',
        //    'property-render',
        //    'log-render',
        //    'election-render',
        //    'formslist-render',
        //    'dashboard-render',
        //    'requests-voters-render',
        //    'dashboard-task-performance',
        //    'request-types-render',
        //    'request-types-create',
        //    'request-types-edit',
        //    'request-types-toggle',
        //    'sub-status-render',
        //    'sub-status-create',
        //    'sub-status-edit',
        //    'sub-status-toggle',
        //    'task-list-render',
        //    'task-edit-render',
        //    'task-edit-update',
        //    // Bulk task assignment permissions
        //    'task-bulk-assign',
        //    'task-bulk-assign-selected',
        //    'task-bulk-assign-filtered',
        //    // Remove assignment permissions
        //    'task-bulk-unassign',
        //    'task-bulk-unassign-selected',
        //    'task-bulk-unassign-filtered',
        // 'admin-dashboard-render',

        // 'user-openSubconsiteModal',
        // 'user-saveUserSubconsites',

        // 'voters-viewVoter',
        // 'voters-openProvisionalPledge',
        // 'voters-openFinalPledge',
        // 'voters-viewProvisionalHistory',

            // Voters export
            // 'voters-exportProvisionalPledgesCsv',
            // 'votedRepresentative-render',
            // 'consites-focals-render',
            // 'voting-dashboard-render',
            // 'votedRepresentative-markAsVoted',

            // Vote results entry
            // 'vote-results-entry-render',
            // 'vote-results-entry-save',

            // Elections management
            // 'elections-manage-render',
            // 'elections-manage-save',
            // 'elections-manage-toggle',

            // Call Center (granular)
            // 'call-center-render',
            'call-center-notes',
            'call-center-call-status',
            'call-center-history',
            'call-center-mark-completed',
            'call-center-undo-status',
        ];
        
        foreach ($permissions as $permission) {
             Permission::firstOrCreate(['name' => $permission]);
        }
    }
}