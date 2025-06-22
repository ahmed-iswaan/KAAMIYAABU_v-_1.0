				<!--begin::Aside-->
				<div id="kt_aside" class="aside aside-default aside-hoverable" data-kt-drawer="true" data-kt-drawer-name="aside" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="{default:'200px', '300px': '250px'}" data-kt-drawer-direction="start" data-kt-drawer-toggle="#kt_aside_toggle">
					<!--begin::Brand-->
					<div class="aside-logo flex-column-auto px-10 pt-9 pb-5" id="kt_aside_logo">
						<!--begin::Logo-->
						<a href="#">
							<img alt="Logo" src="{{ asset('assets/media/logos/logo-default.svg') }}" class="max-h-50px logo-default theme-light-show" />
							<img alt="Logo" src="{{ asset('assets/media/logos/logo-default-dark.svg') }}" class="max-h-50px logo-default theme-dark-show" />
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
								<!--begin:Menu item-->
								</div>
								<!--end:Menu item-->
                                <!--begin:Menu item-->

                                <div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="fw-bold text-muted text-uppercase fs-7">Services</span>
									</div>
									<!--end:Menu content-->
								</div>
                                <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                        <a class="menu-link {{ request()->is('waste') ? 'active' : '' }}" href="/waste">
                                            <span class="menu-icon">
                                                <i class="ki-duotone ki-truck fs-2">
                                                <span class="path1"></span>
                                                <span class="path2"></span>
                                                <span class="path3"></span>
                                                <span class="path4"></span>
                                                <span class="path5"></span>
                                                </i>
                                            </span>
                                            <span class="menu-title">Waste Management</span>
                                        </a>
                                        <!--end:Menu link-->
                                    </div>
                                 <!--end:Menu item-->
                          

								<!--end:Menu item-->
								<!--begin:Menu item-->

                                <div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="fw-bold text-muted text-uppercase fs-7">Island</span>
									</div>
									<!--end:Menu content-->
								</div>
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
                                        <span class="menu-title">Propertys & Lands</span>
                                    </a>
                                    <!--end:Menu link-->
                                 </div>
                                 <!--end:Menu item-->

								<!--end:Menu item-->

								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="fw-bold text-muted text-uppercase fs-7">Finance</span>
									</div>
									<!--end:Menu content-->
								</div>
                                                                 <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                    <a class="menu-link {{ request()->is('invoices') ? 'active' : '' }}" href="/invoices">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-note-2 fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                             </i>
                                        </span>
                                        <span class="menu-title">Invoice</span>
                                    </a>
                                    <!--end:Menu link-->
                                 </div>
                                 <!--end:Menu item-->

                                <!--begin:Menu item-->
                                    <div class="menu-item">
                                        <!--begin:Menu link-->
                                    <a class="menu-link" href="#">
                                        <span class="menu-icon">
                                            <i class="ki-duotone ki-bill fs-2">
                                            <span class="path1"></span>
                                            <span class="path2"></span>
                                            <span class="path3"></span>
                                            <span class="path4"></span>
                                            <span class="path5"></span>
                                            <span class="path6"></span>
                                             </i>
                                        </span>
                                        <span class="menu-title">Payments</span>
                                    </a>
                                    <!--end:Menu link-->
                                 </div>
                                 <!--end:Menu item-->
							
								<!--begin:Menu item-->
								<div class="menu-item pt-5">
									<!--begin:Menu content-->
									<div class="menu-content">
										<span class="fw-bold text-muted text-uppercase fs-7">System</span>
									</div>
									<!--end:Menu content-->
								</div>
								<!--end:Menu item-->


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