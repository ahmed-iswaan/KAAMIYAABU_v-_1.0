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

    {{-- Load Vite assets early so Echo (resources/js/app.js -> bootstrap.js -> echo.js) is available before Livewire boots --}}
    @vite(['resources/sass/app.scss','resources/js/app.js'])
    <script>
        window.Laravel = window.Laravel || {};
        window.Laravel.userId = @json(auth()->id());
        window.Laravel.csrfToken = @json(csrf_token());
    </script>
    <script>
        // Frame-busting to prevent site from being loaded within a frame without permission (click-jacking) if (window.top != window.self) { window.top.location.replace(window.self.location.href); }

    </script>
    @livewireStyles
        @stack('styles')
    {{-- Global small task title & overflow fixes --}}
    <style>
        /* Constrain generic card titles overflowing */
        .card .card-title, .card h3.card-title, .card h4.card-title {white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;}
        /* Task list specific */
        .task-item .task-title{font-size:12px!important;font-weight:600;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.25;}
        .task-item .task-number{max-width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
        /* Ensure container allows truncation */
        .task-item .flex-grow-1{min-width:0;}
        /* Adjust badge sizing for tighter layout */
        .task-item .badge{font-size:10px!important;line-height:1.1;padding:3px 6px;}
        /* Overdue due date force red */
        .task-item .task-due.text-danger, .task-item.active .task-due.text-danger {color:#dc2626 !important;}
        @media (max-width:520px){
            .task-item .task-title{-webkit-line-clamp:3;}
        }
    </style>
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
                    <div class="page-loader flex-column bg-dark bg-opacity-20 justify-content-center align-items-center">
                        <div id="lottie-loader" style="width: 300px; height: 300px;"></div>
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

@livewireScripts
<!-- SweetAlert2 global include & Livewire event bridge -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
/* Ensure SweetAlert always appears above loaders/overlays and centered */
.swal2-container{z-index:99999 !important;}

/* Prevent footer/page from shifting when Swal toggles body scrollbar lock */
body.swal2-shown{padding-right:0 !important;}

/* Prevent SweetAlert2 from forcing page height/overflow changes that can move footer */
html.swal2-shown,
body.swal2-shown{
    height:auto !important;
}
html.swal2-height-auto,
body.swal2-height-auto{
    height:auto !important;
}
</style>
<script>
// Craft / Keenthemes styled SweetAlert2 mixin (centered by default)
if(window.Swal && !window.SwalTheme){
    window.SwalTheme = Swal.mixin({
        customClass: { confirmButton: 'btn btn-primary', cancelButton: 'btn btn-light', denyButton: 'btn btn-danger' },
        buttonsStyling: false,
        focusConfirm: false,
        position: 'center'
    });
}

// Avoid page/footer shifting when Swal opens by disabling scroll-lock padding logic
if(window.Swal){
    try{ Swal.defaults = Swal.defaults || {}; Swal.defaults.scrollbarPadding = false; Swal.defaults.heightAuto = false; } catch(_){ }
}

if(!window.__swalBridge){
    document.addEventListener('livewire:init', () => {
        if(!window.Livewire || window.__swalBridgeAttached) return;
        window.Livewire.on('swal', (payload) => {
            let detail = Array.isArray(payload) ? (payload[0]||{}) : (payload||{});
            const api = window.SwalTheme || window.Swal; if(!api) return;
            const opts = {
                icon: detail.icon || detail.type || 'success',
                title: detail.title || 'Done',
                text: detail.text || '',
                html: detail.html || undefined,
                timer: detail.timer || (detail.showConfirmButton ? undefined : 1500),
                showConfirmButton: detail.showConfirmButton ?? false,
                showCancelButton: !!detail.showCancelButton,
                showDenyButton: !!detail.showDenyButton,
                cancelButtonText: detail.cancelButtonText || 'Cancel',
                confirmButtonText: detail.confirmButtonText || 'OK',
                denyButtonText: detail.denyButtonText || 'No',
                reverseButtons: detail.reverseButtons || false,
                toast: !!detail.toast,
                timerProgressBar: detail.timer && detail.timer > 0 ? true : false,
                allowOutsideClick: detail.allowOutsideClick ?? (!detail.showCancelButton && !detail.showDenyButton),
                scrollbarPadding: false,
                heightAuto: false
            };
            // Only override position if explicitly passed
            if(detail.position){ opts.position = detail.position; }
            if(opts.toast){ // toast uses top-end by default unless overridden
                if(!detail.position) opts.position = 'top-end';
            }
            api.fire(opts).then((res) => {
                if(detail.callbackEvent){
                    try { Livewire.dispatch(detail.callbackEvent, {isConfirmed:res.isConfirmed,isDenied:res.isDenied,isDismissed:res.isDismissed}); } catch(_){}
                }
            });
            try { window.dispatchEvent(new CustomEvent('swal', { detail })); } catch(_){ }
        });
        window.Livewire.on('swal:confirm', (payload) => {
            const d = Array.isArray(payload) ? (payload[0]||{}) : (payload||{});
            const api = window.SwalTheme || window.Swal; if(!api) return;
            api.fire({ icon: d.icon || 'question', title: d.title || 'Are you sure?', text: d.text || '', showCancelButton: true, confirmButtonText: d.confirmButtonText || 'Yes', cancelButtonText: d.cancelButtonText || 'No' })
                .then((r) => {
                    if(r.isConfirmed && d.callbackEvent){
                        try { Livewire.dispatch(d.callbackEvent, d.callbackPayload||{}); } catch(_){}
                    }
                });
        });
        window.__swalBridgeAttached = true;
    });
    window.__swalBridge = true;
}
</script>
<!-- Minimal Echo setup (no UI CSS impact). Must load BEFORE components needing echo: listeners finish booting. -->
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@2.2.0/dist/echo.iife.js"></script>
<script>
// Initialize Echo (if not already) WITHOUT subscribing yet
if(typeof window.Echo === 'undefined') {
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: @json(env('REVERB_APP_KEY')),
            wsHost: @json(env('REVERB_HOST')),
            wsPort: @json(env('REVERB_PORT',8080)),
            wssPort: @json(env('REVERB_PORT',8080)),
            forceTLS: @json(env('REVERB_SCHEME','https')) === 'https',
            enabledTransports: ['ws','wss'],
            auth: { headers: { 'X-CSRF-TOKEN': window.Laravel?.csrfToken || '' } }
        });
        console.log('[Realtime] Echo initialized');
    } catch(e){ console.warn('[Realtime] Echo init failed', e); }
}
</script>
<script>
// SAFE subscription logic (replaces previous direct calls that caused TypeError)
(function realtimeSubscribe(){
    const CHANNEL_PREFIX = 'agent.tasks.';
    let attempts = 0; const maxAttempts = 40; // ~10s (250ms interval)

    function isEchoReady(e){
        return e && typeof e === 'object' && typeof e.private === 'function' && typeof e.channel === 'function';
    }

    function trySubscribe(){
        const uid = window.Laravel?.userId;
        if(!uid){ return schedule(); }
        const echo = window.Echo;
        if(!isEchoReady(echo)){ return schedule(); }
        const channelName = CHANNEL_PREFIX + uid;
        if(window.__agentTasksSubscribed){ return; }
        try {
            echo.private(channelName).listen('.TaskDataChanged', e => {
                const taskId = e.task_id;
                if(!window.Livewire) return;
                try {
                    // Target only agent management components
                    if(typeof Livewire.all === 'function'){
                        Livewire.all().forEach(c => {
                            const name = c.name || c.__instance?.name;
                            if(name === 'agent.agent-management'){
                                try { c.call('handleExternalTaskUpdate', taskId); } catch(_){ }
                            }
                        });
                    } else {
                        // Fallback scan
                        document.querySelectorAll('[wire\\:id]').forEach(el => {
                            const id = el.getAttribute('wire:id');
                            try { const comp = Livewire.find(id); if(comp && comp.name === 'agent.agent-management'){ comp.call('handleExternalTaskUpdate', taskId); } } catch(_){ }
                        });
                    }
                } catch(err){ console.warn('[Realtime] Livewire update failed', err); }
            });
            window.__agentTasksSubscribed = true;
            console.info('[Realtime] Subscribed to', channelName);
        } catch(err){
            console.warn('[Realtime] Subscribe attempt failed (will retry)', err);
            schedule();
        }
    }

    function schedule(){
        if(++attempts >= maxAttempts){ console.warn('[Realtime] Gave up subscribing (Echo not ready).'); return; }
        setTimeout(trySubscribe, 250);
    }

    trySubscribe();
})();
</script>
<script>
    console.log('🔥 Script loaded');
    console.log('Echo present?', typeof window.Echo !== 'undefined');

    document.addEventListener('livewire:init', () => {
        console.log('✅ Livewire is initialized');
        console.log('Echo after Livewire init?', typeof window.Echo !== 'undefined');

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
<script>
// Ensure Echo is a proper instance (handles case where global Echo is constructor from CDN)
(function fixEchoInstance(){
    function build(){
        const isCtor = typeof window.Echo === 'function' && (typeof window.Echo.prototype?.channel === 'function');
        const isInstance = typeof window.Echo === 'object' && typeof window.Echo?.channel === 'function';
        if(isInstance) return true;
        if(isCtor){
            try {
                const EchoClass = window.Echo; // preserve constructor
                window.Echo = new EchoClass({
                    broadcaster:'reverb',
                    key:@json(env('REVERB_APP_KEY')),
                    wsHost:@json(env('REVERB_HOST')),
                    wsPort:@json(env('REVERB_PORT',8080)),
                    wssPort:@json(env('REVERB_PORT',8080)),
                    forceTLS:@json(env('REVERB_SCHEME','https'))==='https',
                    enabledTransports:['ws','wss'],
                });
                console.log('Echo instantiated from constructor');
                return true;
            } catch(e){ console.warn('Echo instantiation failed', e); }
        }
        return false;
    }
    let tries=0; const max=40;
    (function loop(){
        if(build()) { attachBridge(); return; }
        tries++; if(tries<max) return setTimeout(loop,100);
        console.warn('Echo instance not ready');
    })();

    function invokeLivewireHandler(payload){
        if(!window.Livewire) return;
        try {
            window.Livewire.dispatch('reverb-voter-update', payload);
            window.dispatchEvent(new CustomEvent('voter-data-updated', { detail: payload }));
            if(typeof window.Livewire.all === 'function'){
                window.Livewire.all().forEach(c => {
                    if(c.name === 'election.voter-management'){
                        try { c.call('handleRealtimeUpdate', payload); } catch(err){ console.warn('Direct call failed', err); }
                    }
                });
            }
        } catch(err){ console.warn('invokeLivewireHandler error', err); }
    }

    function attachBridge(){
        if(window.__echoBridgeAttached || !window.Echo) return;
        try {
            window.Echo.channel('elections.voters').listen('.VoterDataChanged', e => {
                console.log('🔔 VoterDataChanged (bridge)', e);
                invokeLivewireHandler(e);
            });
            window.__echoBridgeAttached = true;
            console.log('✅ Subscribed to elections.voters (bridge active)');
        } catch(err){ console.warn('Bridge subscribe failed', err); }
    }
})();
</script>
@include('layouts.partials.lottie-scripts')
<script>
(function initEchoAndSubscribe(){
    const cfg = {
        broadcaster: 'reverb',
        key: @json(env('REVERB_APP_KEY')),
        wsHost: @json(env('REVERB_HOST')),
        wsPort: @json(env('REVERB_PORT',8080)),
        wssPort: @json(env('REVERB_PORT',8080)),
        forceTLS: @json(env('REVERB_SCHEME','https')) === 'https',
        enabledTransports: ['ws','wss'],
        auth: { headers: { 'X-CSRF-TOKEN': window.Laravel?.csrfToken || '' } }
    };

    function isInstance(o){ return o && typeof o === 'object' && typeof o.private === 'function' && typeof o.channel === 'function'; }
    function isConstructor(o){ return typeof o === 'function' && o.prototype && typeof o.prototype.channel === 'function'; }

    function ensureInstance(){
        if(isInstance(window.Echo)) return true;
        if(isConstructor(window.Echo)) { try { window.Echo = new window.Echo(cfg); return true; } catch(e){ console.warn('Echo ctor failed', e); return false; } }
        if(typeof window.Echo === 'undefined' && typeof window.Echo === 'undefined' && typeof Echo !== 'undefined' && isConstructor(Echo)){
            try { window.Echo = new Echo(cfg); return true; } catch(e){ console.warn('Echo global ctor failed', e); }
        }
        return false;
    }

    function relayToLivewire(event){
        try {
            // Debug: confirm event reaches browser
            if(window.__debugVotingEvents){
                console.debug('[Echo] RepresentativeVotedChanged', event);
            }
            if(window.Livewire){
                window.Livewire.dispatch('representative-voted-changed', event);
            }
        } catch(e){ console.warn('Relay to Livewire failed', e); }
    }

    function subscribe(){
        if(!window.Echo || window.__echoRepresentativesAttached) return;
        try {
            window.Echo.channel('elections.representatives').listen('.RepresentativeVotedChanged', (e) => {
                relayToLivewire(e);
            });
            window.__echoRepresentativesAttached = true;
        } catch(e){ console.warn('Representatives subscribe failed', e); }
    }

    let tries=0; const max=40;
    (function loop(){
        if(ensureInstance()) { subscribe(); return; }
        tries++; if(tries<max) return setTimeout(loop,100);
    })();
})();
</script>
@stack('scripts')
</body>

</html>
