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
use App\Livewire\Tasks\TaskAssignment;
use App\Http\Controllers\Auth\LoginController;
use App\Livewire\System\RequestTypesManagement; // added



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
Route::get('/agents', AgentManagement::class)->name('agents');
Route::get('/tasks/assign', TaskAssignment::class)->name('tasks.assign');

// Forms
Route::get('/forms', FormsList::class)->name('forms.index');
Route::get('/forms/create', FormBuilder::class)->name('forms.create');
Route::get('/forms/{form}/edit', FormBuilder::class)->name('forms.edit');

// Replace controller-based request types with Livewire component
Route::get('/system/request-types', RequestTypesManagement::class)->name('request-types.index');

});