<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Kaamiyaabu') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Primary Meta -->
    <meta name="application-name" content="Kaamiyaabu">
    <meta name="description" content="Kaamiyaabu – Municipal Property & Election Engagement Portal">

    <!-- Google Fonts -->
    <link href="https://fonts.bunny.net/css?family=Inter:300,400,500,600,700" rel="stylesheet">

    <!-- Core CSS -->
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    <!-- Icons -->
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/media/logos/logo-compact.svg') }}">
    <link rel="apple-touch-icon" href="{{ asset('assets/media/logos/logo-compact.svg') }}">

    @stack('styles')
</head>
<body id="kt_body" class="d-flex flex-column">

    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
        <div class="shape shape-5"></div>
        <div class="shape shape-6"></div>
    </div>

    <style>
        body {
            background-color: #1a2a4a; /* Dark blue background */
        }

        .background-shapes {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            overflow: hidden;
            z-index: 0;
        }

        .shape {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            animation: float 25s linear infinite;
            bottom: -150px;
        }

        .shape-1 { left: 25%; width: 80px; height: 80px; animation-delay: 0s; }
        .shape-2 { left: 10%; width: 20px; height: 20px; animation-delay: 2s; animation-duration: 12s; }
        .shape-3 { left: 70%; width: 20px; height: 20px; animation-delay: 4s; }
        .shape-4 { left: 40%; width: 60px; height: 60px; animation-delay: 0s; animation-duration: 18s; }
        .shape-5 { left: 65%; width: 20px; height: 20px; animation-delay: 0s; }
        .shape-6 { left: 85%; width: 110px; height: 110px; animation-delay: 3s; }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 1;
                border-radius: 0;
            }
            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
                border-radius: 50%;
            }
        }
        
        /* Ensure content is on top of the background */
        .login-container {
            position: relative;
            z-index: 1;
        }
    </style>

    @yield('content')

    <!-- Core JS -->
    <script>var hostUrl = "{{ asset('assets') }}/";</script>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
    
    @stack('scripts')
</body>
</html>
