<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\DashboardOverview;
use App\Livewire\Roles\RolesManagement;
use App\Livewire\User\UserManagement;
use App\Livewire\Directory\DirectoryManagement;
use App\Livewire\Property\PropertyManagement;
use App\Livewire\Property\ViewProperty;
use App\Livewire\Invoice\InvoiceManager;


Route::get('/', function () {
    return redirect()->route('login');
});


Auth::routes();

Route::get('/dashboard', DashboardOverview::class)->name('dashboard');

Route::group(['middleware' => ['auth']], function() {


Route::get('/roles', RolesManagement::class)->name('roles');
Route::get('/users', UserManagement::class)->name('users');
Route::get('/directory', DirectoryManagement::class)->name('directory');
Route::get('/properties', PropertyManagement::class)->name('properties');
Route::get('properties/{property}', ViewProperty::class)
         ->name('properties.view')
         ->whereUuid('property');
         
Route::get('/invoices', InvoiceManager::class)
         ->name('invoices.index');
});