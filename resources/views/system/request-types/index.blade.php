@extends('layouts.master')
@section('title','Request Types')
@section('content')
<div class="container-fluid py-5">
    <div class="card shadow-sm mb-5">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Request Types</h3>
            <form class="d-flex gap-2" method="POST" action="{{ route('request-types.store') }}">
                @csrf
                <input type="text" name="name" class="form-control form-control-sm" placeholder="New type name" required>
                <input type="text" name="description" class="form-control form-control-sm" placeholder="Description (optional)">
                <button class="btn btn-sm btn-primary" type="submit">Add</button>
            </form>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($types as $t)
                    <tr>
                        <td>
                            <form method="POST" action="{{ route('request-types.update',$t) }}" class="d-flex align-items-center gap-2">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $t->name }}" class="form-control form-control-sm" style="max-width:180px;">
                                <input type="text" name="description" value="{{ $t->description }}" class="form-control form-control-sm" style="max-width:240px;">
                                <input type="hidden" name="active" value="{{ $t->active ? 1 : 0 }}">
                                <button class="btn btn-sm btn-light">Save</button>
                            </form>
                        </td>
                        <td class="text-muted small">{{ $t->description }}</td>
                        <td>
                            <span class="badge {{ $t->active ? 'badge-light-success' : 'badge-light-danger' }}">{{ $t->active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('request-types.toggle',$t) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <button class="btn btn-sm {{ $t->active ? 'btn-danger' : 'btn-success' }}">{{ $t->active ? 'Deactivate' : 'Activate' }}</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                    @if(!count($types))
                        <tr><td colspan="4" class="text-center text-muted py-5">No request types.</td></tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
