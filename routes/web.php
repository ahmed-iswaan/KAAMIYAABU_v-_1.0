<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\DashboardOverview;
use App\Livewire\Roles\RolesManagement;
use App\Livewire\User\UserManagement;
use App\Livewire\Directory\DirectoryManagement;
use App\Livewire\Property\PropertyManagement;
use App\Livewire\Property\ViewProperty;
use App\Livewire\Election\VoterManagement;
use App\Livewire\Election\RequestsManagement;
use App\Livewire\Agent\AgentManagement;
use App\Livewire\Forms\FormsList;
use App\Livewire\Forms\FormBuilder;
use App\Livewire\Forms\FormResponses; // added
use App\Livewire\Tasks\TaskAssignment;
use App\Livewire\Tasks\TaskList; // added
use App\Livewire\Tasks\TaskEdit; // added
use App\Http\Controllers\Auth\LoginController;
use App\Livewire\System\RequestTypesManagement; // added
use App\Livewire\System\SubStatusManagement; // added
use App\Http\Controllers\RequestReportController;


Route::get('/', function () {
    return redirect()->route('login');
});

// Remove default Auth::routes() and manually define only login/logout
Route::get('login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('login', [LoginController::class, 'login']);
Route::post('logout', [LoginController::class, 'logout'])->name('logout');

Route::group(['middleware' => ['auth']], function() {

    Route::get('/dashboard', DashboardOverview::class)->name('dashboard');
    Route::get('/roles', RolesManagement::class)->name('roles');
    Route::get('/users', UserManagement::class)->name('users');
    Route::get('/directory', DirectoryManagement::class)->name('directory');
    Route::get('/properties', PropertyManagement::class)->name('properties');
    // Route::get('properties/{property}', ViewProperty::class)
    //          ->name('properties.view')
    //          ->whereUuid('property');
    Route::get('/elections/voters', VoterManagement::class)->name('elections.voters');
    Route::get('/elections/requests', RequestsManagement::class)->name('elections.requests');
    Route::get('/elections/requests/export', [RequestReportController::class, 'exportRequests'])->name('elections.requests.export');
    Route::get('/agents', AgentManagement::class)->name('agents');
    Route::get('/tasks/assign', TaskAssignment::class)->name('tasks.assign');
    Route::get('/tasks', TaskList::class)->name('tasks.index'); // added
    Route::get('/tasks/{task}/edit', TaskEdit::class)->name('tasks.edit')->whereUuid('task'); // added

    // Forms
    Route::get('/forms', FormsList::class)->name('forms.index');
    Route::get('/forms/create', FormBuilder::class)->name('forms.create');
    Route::get('/forms/{form}/edit', FormBuilder::class)->name('forms.edit');
    Route::get('/forms/{form}/responses', FormResponses::class)->name('forms.responses');
    Route::get('/forms/{form}/responses/export-options', [\App\Http\Controllers\FormResponsesExportController::class,'exportOptionRespondents'])->name('forms.responses.export.options');
    Route::get('/forms/{form}/responses/export-option/{question}/{optionValue}', [\App\Http\Controllers\FormResponsesExportController::class,'exportSingleOption'])
        ->whereUuid('form')
        ->whereUuid('question')
        ->name('forms.responses.export.single');

    // System
    Route::get('/system/request-types', RequestTypesManagement::class)->name('request-types.index');
    Route::get('/system/sub-statuses', SubStatusManagement::class)->name('sub-statuses.index'); // added

    // Admin dashboard
    Route::get('/admin/dashboard', \App\Livewire\Admin\AdminDashboard::class)->name('admin.dashboard');
    Route::get('/elections/representatives', \App\Livewire\Election\Representatives::class)->name('elections.representatives');

    Route::get('/elections/consites-focals', \App\Livewire\Election\ConsiteFocals::class)
        ->name('elections.consites-focals')
        ->middleware('permission:consites-focals-render');

    Route::get('/elections/voting-dashboard', \App\Livewire\Election\VotingDashboard::class)
        ->name('elections.voting-dashboard')
        ->middleware('permission:voting-dashboard-render');
});