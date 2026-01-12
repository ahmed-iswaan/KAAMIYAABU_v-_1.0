<!--begin::Aside-->
				<div id="kt_aside" class="aside aside-default aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_toggle">
					<!--begin::Brand-->
					<div class="aside-logo flex-column-auto px-10 pt-9 pb-5" id="kt_aside_logo">
						<!--begin::Logo-->
						<a href="#">
							<img alt="Logo" src="{{ asset('assets/media/logos/logo-default.svg') }}" class="max-h-50px logo-default theme-light-show" style="width: 200px;" />
							<img alt="Logo" src="{{ asset('assets/media/logos/logo-default-dark.svg') }}" class="max-h-50px logo-default theme-dark-show" style="width: 200px;" />
							<img alt="Logo" src="{{ asset('assets/media/logos/logo-minimize.svg') }}" class="max-h-50px logo-minimize" />
						</a>
						<!--end::Logo-->
					</div>
					<!--end::Brand-->
					<!--begin::Aside menu-->
					<div class="aside-menu flex-column-fluid ps-3 pe-1">
						<!--begin::Aside Menu-->
                        
						<!--begin::Menu-->
						<div class="menu menu-sub-indention menu-column menu-rounded menu-title-gray-600 menu-icon-gray-400 menu-active-bg menu-state-primary menu-arrow-gray-500 fw-semibold fs-6 my-5 mt-lg-2 mb-lg-0" id="kt_aside_menu" data-kt-menu="true">
							<div class="hover-scroll-y mx-4" id="kt_aside_menu_wrapper" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-height="auto" data-kt-scroll-wrappers="#kt_aside_menu" data-kt-scroll-offset="20px" data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer">
								<!--begin:Menu item-->
                                 @can('dashboard-render')
								<div data-kt-menu-trigger="click" class="menu-item here show menu-accordion">
    
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link {{ request()->is('dashboard') ? 'active' : '' }}" href="{{ url('/dashboard') }}">
                                                <span class="menu-icon">
                                                <i class="ki-duotone ki-element-11 fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                </i>
                                            </span>
                                            <span class="menu-title">Dashboard</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                  @endcan    
                                  @can('admin-dashboard-render')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->is('admin/dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                            <span class="menu-icon">
                                           <i class="ki-duotone ki-element-11 fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                </i>
                                            </span>
                                            <span class="menu-title">Admin Dashboard</span>
                                        </a>
                                    </div>
                                    @endcan
                                  @can('agent-render')                               <!--begin:Menu item (Agents) -->
                                 <div class="menu-item">
                                    <a class="menu-link {{ request()->is('agents') ? 'active' : '' }}" href="/agents">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-people fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Call Agents</span>
                                    </a>
                                </div>
                                @endcan  
                                @can('voters-render') 
                                <!--end:Menu item (Agents) -->
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link {{ request()->is('elections/voters') ? 'active' : '' }}" href="{{ route('elections.voters') }}">
                                                <span class="menu-icon">
                                               <i class="ki-duotone ki-people">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                                <span class="path5"></span>
                                                </i>
                                            </span>
                                            <span class="menu-title">Pledges</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    @endcan

                                    <!-- Representatives menu item -->
                                    @can('votedRepresentative-render')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->is('elections/representatives') ? 'active' : '' }}" href="{{ route('elections.representatives') }}">
                                            <span class="menu-icon">
                                               <i class="ki-duotone ki-directbox-default fs-2">
                                                <span class="path1"></span>
                                        <span class="path2"></span>
                                        <span class="path3"></span>
                                        <span class="path4"></span>
                                               </i>
                                            </span>
                                            <span class="menu-title">Mandhoob</span>
                                        </a>
                                    </div>
                                    @endcan

                                    <!-- Consites Focals menu item -->
                                    @can('consites-focals-render')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->is('elections/consites-focals') ? 'active' : '' }}" href="{{ route('elections.consites-focals') }}">
                                            <span class="menu-icon">
                                                <i class="ki-duotone ki-ranking fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                </i>
                                            </span>
                                            <span class="menu-title">Consites Focals</span>
                                        </a>
                                    </div>
                                    @endcan

                                    <!-- Voting Dashboard menu item -->
                                    @can('voting-dashboard-render')
                                    <div class="menu-item">
                                        <a class="menu-link {{ request()->is('elections/voting-dashboard') ? 'active' : '' }}" href="{{ route('elections.voting-dashboard') }}">
                                            <span class="menu-icon">
                                                <i class="ki-duotone ki-chart-simple fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                    <span class="path3"></span>
                                                    <span class="path4"></span>
                                                </i>
                                            </span>
                                            <span class="menu-title">Voting Dashboard</span>
                                        </a>
                                    </div>
                                    @endcan
                                <!--end:Menu item-->
                                 @can('requests-voters-render') 
                                    <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link {{ request()->is('elections/requests') ? 'active' : '' }}" href="{{ route('elections.requests') }}">
                                                <span class="menu-icon">
                                                <i class="ki-duotone ki-message-text-2 fs-2">
                                                    <span class="path1"></span>
                                                    <span class="path2"></span>
                                                </i>
                                            </span>
                                            <span class="menu-title">Requests</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                    <!--end:Menu item-->
                                <!--begin:Menu item-->
                                   @endcan

                                </div>
                                
								<!--end:Menu item-->


							  @canany(['directory-render','property-render','task-render','formslist-render','user-render','role-render'])
								<!--begin:Menu item-->
								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="fw-bold text-muted text-uppercase fs-7">System</span>
									</div>
									<!--end:Menu content-->
								</div>
                                 @endcanany
								<!--end:Menu item-->
                                    @can('directory-render')
                                                                <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link {{ request()->is('directory') ? 'active' : '' }}" href="/directory">
                                            <span class="menu-icon">
                                                <i class="ki-duotone ki-address-book fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                </i>
                                            </span>
                                            <span class="menu-title">Directory</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                 <!--end:Menu item-->
                                 @endcan
                                 @can('property-render')
                                 <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                    <a class="menu-link {{ request()->is('properties') ? 'active' : '' }}" href="/properties">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-geolocation-home fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                             </i>
                                        </span>
                                        <span class="menu-title">Address</span>
                                    </a>
                                    <!--end:Menu link-->
                                 </div>
                                 <!--end:Menu item-->  
                                 @endcan
                                @can('task-render')
                                <!--begin:Menu item (Task Assignment) -->
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('tasks') ? 'active' : '' }}" href="{{ route('tasks.index') }}">
                                        <span class="menu-icon">
                                           <i class="ki-duotone ki-text-circle">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                            <span class="path6"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Task Assignment</span>
                                    </a>
                                </div>
                                <!--end:Menu item (Task Assignment) -->
                                @endcan
                                @can('formslist-render')
                                <!--begin:Menu item (Form Builder) -->
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('forms') || request()->is('forms/*') ? 'active' : '' }}" href="{{ route('forms.index') }}">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-notepad-edit fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Form Builder</span>
                                    </a>
                                </div>
                                <!--end:Menu item (Form Builder) -->
                                @endcan
                                @can('request-types-render')
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('system/request-types') ? 'active' : '' }}" href="{{ route('request-types.index') }}">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-category fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Request Types</span>
                                    </a>
                                </div>
                                @endcan
                                @can('sub-status-render')
                                <div class="menu-item">
                                    <a class="menu-link {{ request()->is('system/sub-statuses') ? 'active' : '' }}" href="{{ route('sub-statuses.index') }}">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-setting-2 fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                            </i>
                                        </span>
                                        <span class="menu-title">Sub Status Types</span>
                                    </a>
                                </div>
                                @endcan
                                @can('user-render')
                               <!--begin:Menu item-->
                                  <div class="menu-item">
                                 <!--begin:Menu link-->
                                    <a class="menu-link {{ request()->is('users') ? 'active' : '' }}" href="/users">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-profile-user fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                             </i>
                                        </span>
                                        <span class="menu-title">Users</span>
                                    </a>
                                    <!--end:Menu link-->
                                 </div>
                                 <!--end:Menu item-->
                                @endcan  
                                @can('user-render')
                                <!--begin:Menu item-->
                                 <div class="menu-item">
                                 <!--begin:Menu link-->
                                    <a class="menu-link {{ request()->is('roles') ? 'active' : '' }} " href="/roles">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-security-user fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                             </i>
                                        </span>
                                        <span class="menu-title">Roles</span>
                                    </a>
                                    <!--end:Menu link-->
                                 </div>
                                 <!--end:Menu item-->
								@endcan
                             @can('log-render')
								<!--begin:Menu item-->
								<div class="menu-item">
									<!--begin:Menu content-->
									<div class="menu-content">
										<div class="separator mx-1 my-4"></div>
									</div>
									<!--end:Menu content-->
								</div>
								<!--end:Menu item-->
								<!--begin:Menu item-->
                          
								<div class="menu-item">
									<!--begin:Menu link-->
									<a class="menu-link" href="#">
										<span class="menu-icon">
											<i class="ki-duotone ki-code fs-2">
												<span class="path1"></span>
												<span class="path2"></span>
												<span class="path3"></span>
												<span class="path4"></span>
											</i>
										</span>
										<span class="menu-title">Logs</span>
									</a>
									<!--end:Menu link-->
								</div>
								<!--end:Menu item-->
                                @endcan
							</div>
						</div>
						<!--end::Menu-->
					</div>
					<!--end::Aside menu-->
					<!--begin::Footer-->
					<div class="aside-footer flex-column-auto pb-5 d-none" id="kt_aside_footer">
						<a href="#" class="btn btn-light-primary w-100">Button</a>
					</div>
					<!--end::Footer-->
				</div>
				<!--end::Aside-->