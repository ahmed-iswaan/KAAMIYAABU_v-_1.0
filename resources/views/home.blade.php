@extends('layouts.master')

@section('title', 'Dashboard')

@section('content')
<!--begin::Toolbar-->
	<div class="toolbar" id="kt_toolbar">
		<div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
			<!--begin::Info-->
		<div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
			<!--begin::Title-->
				<h1 class="text-dark fw-bold my-1 fs-2">Dashboard
					<small class="text-muted fs-6 fw-normal ms-1"></small></h1>
				<!--end::Title-->
		</div>
			<!--end::Info-->
				<!--begin::Actions-->
					<div class="d-flex align-items-center flex-nowrap text-nowrap py-1">
					<a href="#" class="btn bg-body btn-color-gray-700 btn-active-primary me-4" data-bs-toggle="modal" data-bs-target="#kt_modal_invite_friends">Invite Friends</a>
					<a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#kt_modal_create_project" id="kt_toolbar_primary_button">New Project</a>
					</div>
				<!--end::Actions-->
		</div>
	</div>
<!--end::Toolbar-->

<livewire:dashboard-overview />

    <div class="container-fluid">
        <div class="card">
            <div class="card-body">
                <h3 class="mb-5">Welcome to Council Property System</h3>
               
            </div>
        </div>
    </div>


@endsection
