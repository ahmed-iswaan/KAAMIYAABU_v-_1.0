				<!--begin::Content-->
					<div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">
						<!--begin::Toolbar-->
						<div class="toolbar" id="kt_toolbar">
							<div class="container-fluid d-flex flex-stack flex-wrap flex-sm-nowrap">
								<!--begin::Info-->
								<div class="d-flex flex-column align-items-start justify-content-center flex-wrap me-2">
									<!--begin::Title-->
									<h1 class="text-dark fw-bold my-1 fs-2 d-none d-md-block">Waste Collection</h1>
									<!--end::Title-->
									<!--begin::Breadcrumb-->
									<ul class="breadcrumb fw-semibold fs-base my-1">
										<li class="breadcrumb-item text-muted">
											<a href="/waste-collection" class="text-muted text-hover-primary">Waste Collection</a>
										</li>
										<li class="breadcrumb-item text-dark">Collection List</li>
									</ul>
									<!--end::Breadcrumb-->
								</div>
								<!--end::Info-->

							</div>
						</div>
						<!--end::Toolbar-->
						    @include('livewire.waste.collection-form')
						<!--begin::Post-->
						<div class="post fs-6 d-flex flex-column-fluid" id="kt_post">
							<!--begin::Container-->
							<div class="container-xxl">
								<!--begin::Layout-->
								<div class="d-flex flex-column flex-lg-row">

									<!--begin::Content-->
									<div class="flex-lg-row-fluid ms-lg-15">
										<!--begin:::Tabs-->
                                        <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fw-semibold mb-8 mt-6 flex-wrap flex-md-nowrap">
                                            @foreach(['pending', 'in_progress', 'completed', 'cancelled'] as $status)
                                                <li class="nav-item">
                                                    <a class="nav-link text-active-primary pb-4 {{ $statusFilter === $status ? 'active' : '' }} fs-7 fs-sm-6 fs-md-4"
                                                    href="#"
                                                    wire:click.prevent="$set('statusFilter', '{{ $status }}')">
                                                    {{ ucfirst(str_replace('_', ' ', $status)) }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>

										<!--end:::Tabs-->
										<!--begin:::Tab content-->
										<div class="tab-content" >
											<!--begin:::Tab pane-->
											<div class="tab-pane fade show active" id="kt_user_view_overview_tab" role="tabpanel">
												<!--begin::Card-->
												<div class="card card-flush mb-6 mb-xl-9">

													<!--end::Card header-->
													<!--begin::Card body-->
													<div class="card-body p-9 pt-4">
														<!--begin::Dates-->
									            	<ul class="nav nav-pills d-flex flex-nowrap hover-scroll-x py-2">
                                                        @foreach ($dates as $index => $date)
                                                            <li class="nav-item me-1">
                                                                <a class="nav-link btn d-flex flex-column flex-center rounded-pill min-w-40px me-2 py-4 btn-active-primary {{ $scheduledDate === $date['value'] ? 'active' : '' }}"
                                                                href="#"
                                                                wire:click="$set('scheduledDate', '{{ $date['value'] }}')">
                                                                    <span class="opacity-50 fs-7 fw-semibold">{{ $date['label'] }}</span>
                                                                    <span class="fs-6 fw-bolder">{{ $date['day'] }}</span>
                                                                </a>
                                                            </li>
                                                        @endforeach
                                                    </ul>

														<!--end::Dates-->
														<!--begin::Tab Content-->
										               <div class="tab-content">
                                                            @forelse ($groupedTasks as $time => $group)
                                                                <div class="tab-pane fade show active">
                                                                    @foreach ($group as $task)
                                                                        <div class="d-flex flex-stack position-relative mt-6">
                                                                            <div class="position-absolute h-100 w-4px bg-secondary rounded top-0 start-0"></div>
                                                                            <div class="fw-semibold ms-5">
                                                                                <div class="fs-7 mb-1">{{ $time }}</div>
                                                                                <a href="#" class="fs-5 fw-bold text-dark text-hover-primary mb-2" wire:click.prevent="openModal('{{ $task->register->register_number }}')">
                                                                                    {{ $task->property->name ?? 'N/A' }} /  ({{ $task->register->register_number ?? '-' }})
                                                                                </a>
																				 <div class="fs-7 text-muted">Floor: {{ $task->register->floor ?? '-' }}</div>
                                                                                <div class="fs-7 text-muted">Owner: {{ $task->directory->name ?? 'No Directory' }}</div>
                                                                                <div class="fs-7 text-muted">Vehicle: {{ $task->vehicle->registration_number ?? 'N/A' }} | Driver: {{ $task->driver->name ?? 'N/A' }}</div>
                                                                            </div>
                                                                               <a href="#" class="btn btn-light btn-active-light-primary btn-sm"
																					wire:click.prevent="openModal('{{ $task->register->register_number }}')">
																					View
																				</a>

                                                                        </div>
                                                                    @endforeach
                                                                </div>
                                                            @empty
                                                                <div class="text-center text-muted py-10">No tasks found for {{ $scheduledDate }} with status "{{ $statusFilter }}".</div>
                                                            @endforelse
                                                        </div>
														<!--end::Tab Content-->
													</div>
													<!--end::Card body-->
												</div>
												<!--end::Card-->
	
											</div>
											<!--end:::Tab pane-->

										</div>
										<!--end:::Tab content-->
									</div>
									<!--end::Content-->
								</div>
								<!--end::Layout-->
								
							</div>
							<!--end::Container-->
						</div>
						<!--end::Post-->
					</div>
					<!--end::Content-->