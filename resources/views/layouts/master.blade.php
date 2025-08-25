<!DOCTYPE html>
<html lang="en">
<!--begin::Head-->

<head>
    <base href="" />
    <title>KAAMIYAABU | @yield('title', 'Council Property System')</title>
    <meta charset="utf-8" />
    <meta name="description"
        content="KAAMIYAABU is a municipal property management portal built for local governments in the Maldives—track assets, manage leases, automate land‐rent and more." />
    <meta name="keywords"
        content="council property, land management, Maldives, municipal software, rent portal, property dashboard, local government" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />

    <!-- Open Graph / Facebook -->
    <meta property="og:locale" content="en_MV" />
    <meta property="og:type" content="website" />
    <meta property="og:title" content="@yield('title', 'Council Property System') — KAAMIYAABU" />
    <meta property="og:description"
        content="Streamline your municipality’s property and land‐rent processes with KAAMIYAABU." />
    <meta property="og:url" content="{{ config('app.url') }}" />
    <meta property="og:site_name" content="KAAMIYAABU" />

    <!-- Canonical -->
    <link rel="canonical" href="{{ url()->current() }}" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.svg') }}" />
    <!--begin::Fonts(mandatory for all pages)-->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <!--end::Fonts-->
    <!--begin::Vendor Stylesheets(used for this page only)-->
    <link href="{{ asset('assets/plugins/custom/leaflet/leaflet.bundle.css') }}"
        rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}"
        rel="stylesheet" type="text/css" />
    <!--end::Vendor Stylesheets-->
    <!--begin::Global Stylesheets Bundle(mandatory for all pages)-->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet"
        type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!--end::Global Stylesheets Bundle-->
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }

    </script>
    @livewireStyles
        @stack('styles')

</head>
<!--end::Head-->
<!--begin::Body-->

<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled aside-fixed aside-default-enabled"
    data-kt-app-page-loading-enabled="true" data-kt-app-page-loading="on">
    <!--begin::Theme mode setup on page load-->
    <script>
        var defaultThemeMode = "light";
        var themeMode;
        if (document.documentElement) {
            if (document.documentElement.hasAttribute("data-bs-theme-mode")) {
                themeMode = document.documentElement.getAttribute("data-bs-theme-mode");
            } else {
                if (localStorage.getItem("data-bs-theme") !== null) {
                    themeMode = localStorage.getItem("data-bs-theme");
                } else {
                    themeMode = defaultThemeMode;
                }
            }
            if (themeMode === "system") {
                themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
            }
            document.documentElement.setAttribute("data-bs-theme", themeMode);
        }

    </script>
    <!--end::Theme mode setup on page load-->
    <!--begin::Main-->
    <!--begin::Root-->
    <div class="d-flex flex-column flex-root">
        <!--begin::Page-->
        <div class="page d-flex flex-row flex-column-fluid">
            @include('partials.aside')
            <!--begin::Wrapper-->
            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
                <!--begin::Header-->
                <div id="kt_header" class="header" data-kt-sticky="true" data-kt-sticky-name="header"
                    data-kt-sticky-offset="{default: '200px', lg: '300px'}">
                    <!--begin::Container-->
                    <div class="container-fluid d-flex align-items-stretch justify-content-between">
                        <!--begin::Logo bar-->
                        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
                            <!--begin::Aside Toggle-->
                            <div class="d-flex align-items-center d-lg-none">
                                <div class="btn btn-icon btn-active-color-primary ms-n2 me-1" id="kt_aside_toggle">
                                    <i class="ki-duotone ki-abstract-14 fs-1">
                                        <span class="path1"></span>
                                        <span class="path2"></span>
                                    </i>
                                </div>
                            </div>
                            <!--end::Aside Toggle-->
                            <!--begin::Logo-->
                            <a href="#" class="d-lg-none">
                                <img alt="Logo"
                                    src="{{ asset('assets/media/logos/logo-compact.svg') }}"
                                    class="mh-40px" />
                            </a>
                            <!--end::Logo-->
                            <!--begin::Aside toggler-->
                            <div class="btn btn-icon w-auto ps-0 btn-active-color-primary d-none d-lg-inline-flex me-2 me-lg-5"
                                data-kt-toggle="true" data-kt-toggle-state="active" data-kt-toggle-target="body"
                                data-kt-toggle-name="aside-minimize">
                                <i class="ki-duotone ki-black-left-line fs-1 rotate-180">
                                    <span class="path1"></span>
                                    <span class="path2"></span>
                                </i>
                            </div>
                            <!--end::Aside toggler-->
                        </div>
                        <!--end::Logo bar-->
                        @include('partials.topbar')
                    </div>
                    <!--end::Container-->
                </div>
                <!--end::Header-->
                <!--begin::Content-->
                <div class="content fs-6 d-flex flex-column flex-column-fluid" id="kt_content">

                    <!--begin::Page loading(append to body)-->
                    <div
                        class="page-loader flex-column bg-dark bg-opacity-20 justify-content-center align-items-center">
                        <img src="{{ asset('assets/media/logos/logo.gif') }}" alt="Loading..."
                            style="width: 300px; height: 300px;">
                        <span class="text-white fs-6 fw-semibold mt-5">Please wait...</span>
                    </div>
                    <!--end::Page loading-->

                    <!--begin::Post-->
                    {{ $slot }}
                    <!--end::Post-->
                </div>
                <!--end::Content-->

                @include('partials.footer')

            </div>
            <!--end::Wrapper-->
        </div>
        <!--end::Page-->
    </div>
    <!--end::Root-->
    <!--end::Main-->
    <!--begin::Scrolltop-->
    <div id="kt_scrolltop" class="scrolltop" data-kt-scrolltop="true">
        <i class="ki-duotone ki-arrow-up">
            <span class="path1"></span>
            <span class="path2"></span>
        </i>
    </div>
    <!--end::Scrolltop-->

    <!--begin::Javascript-->
    <script>
        var hostUrl = "assets/";

    </script>
    
    <!--begin::Global Javascript Bundle(mandatory for all pages)-->
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    <!--end::Global Javascript Bundle-->
    <!--begin::Vendors Javascript(used for this page only)-->
    <script src="{{ asset('assets/plugins/custom/leaflet/leaflet.bundle.js') }}"></script>
    <script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}">
    </script>
    <!--end::Vendors Javascript-->
    <!--begin::Custom Javascript(used for this page only)-->
    <script src="{{ asset('assets/js/widgets.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/custom/widgets.js') }}"></script>
    <script src="{{ asset('assets/js/custom/apps/chat/chat.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/upgrade-plan.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/create-project/type.js') }}">
    </script>
    <script
        src="{{ asset('assets/js/custom/utilities/modals/create-project/budget.js') }}">
    </script>
    <script
        src="{{ asset('assets/js/custom/utilities/modals/create-project/settings.js') }}">
    </script>
    <script src="{{ asset('assets/js/custom/utilities/modals/create-project/team.js') }}">
    </script>
    <script
        src="{{ asset('assets/js/custom/utilities/modals/create-project/targets.js') }}">
    </script>
    <script
        src="{{ asset('assets/js/custom/utilities/modals/create-project/files.js') }}">
    </script>
    <script
        src="{{ asset('assets/js/custom/utilities/modals/create-project/complete.js') }}">
    </script>
    <script src="{{ asset('assets/js/custom/utilities/modals/create-project/main.js') }}">
    </script>
    <script src="{{ asset('assets/js/custom/utilities/modals/select-location.js') }}">
    </script>
    <script src="{{ asset('assets/js/custom/utilities/modals/create-app.js') }}"></script>
    <script src="{{ asset('assets/js/custom/utilities/modals/users-search.js') }}"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <!--end::Custom Javascript-->
    <!--end::Javascript-->

@livewireScripts          <!-- Load Livewire JavaScript first -->
@stack('scripts')  
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('swal', (eventData) => {

                const data = eventData[0];
                Swal.fire({
                    text: data.text,
                    icon: data.icon,
                    buttonsStyling: false,
                    confirmButtonText: data.confirmButtonText,
                    customClass: {
                        confirmButton: data.confirmButton
                    }
                });
            });
        });

    </script>

    <script>
    console.log('🔥 Script loaded');

    document.addEventListener('livewire:init', () => {
        console.log('✅ Livewire is initialized');

        // Initial KTMenu setup
        requestAnimationFrame(() => {
            console.log('🔁 Reinitializing KTMenu after initial load');
            if (typeof KTMenu !== 'undefined') {
                KTMenu.createInstances();
            }
        });

        // After Livewire search or updates
        Livewire.on('TableUpdated', () => {
            console.log('🔁 Reinitializing KTMenu after Livewire search');

            // Delay to ensure DOM is ready
            setTimeout(() => {
                if (typeof KTMenu !== 'undefined') {
                    console.log('📦 Running KTMenu.createInstances() after delay');
                    KTMenu.createInstances();
                } else {
                    console.warn('⚠ KTMenu not found');
                }
            }, 50);
        });

        // Just in case general DOM morphing occurs
        Livewire.hook('morph.finished', () => {
            console.log('🔁 Reinitializing KTMenu after morph.finished');
            if (typeof KTMenu !== 'undefined') {
                KTMenu.createInstances();
            }
        });
    });
</script>
</body>
<!--end::Body-->

</html>
