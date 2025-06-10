{{-- resources/views/roles/index.blade.php --}}
@extends('layouts.master')  {{-- or whatever your layout filename is --}}

@section('title', 'Manage Roles')

@section('content')
  <div class="container-fluid">
    <h1 class="mb-4">Role & Permission Management</h1>

    {{-- This injects your Livewire component --}}
    <livewire:roles.role-manager />
  </div>
@endsection
