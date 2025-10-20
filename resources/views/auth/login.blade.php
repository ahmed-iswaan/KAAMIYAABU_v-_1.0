@extends('layouts.auth')

@push('styles')
<style>
    :root {
        --bs-primary-rgb: 0, 149, 255;
        --bs-primary: #0095ff;
    }
    body {
        background-color: #f0f4f9;
    }
    .login-container {
        min-height: 100vh;
    }
    .login-card {
        width: 100%;
        max-width: 450px;
        border-radius: 1.25rem;
        border: none;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        overflow: hidden; /* Ensures content respects border radius */
    }
    .brand-logo img {
        height: 65px; /* Increased logo size */
    }
    .form-control-solid {
        background-color: #f5f8fa;
        border-color: #f5f8fa;
    }
    .form-control-solid:focus {
        background-color: #fff;
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 .25rem rgba(var(--bs-primary-rgb), .15);
    }
    .btn-primary {
        background-color: var(--bs-primary);
        border-color: var(--bs-primary);
        font-weight: 600;
        letter-spacing: .3px;
        transition: all .3s;
    }
    .btn-primary:hover {
        background-color: #007ed6;
        border-color: #007ed6;
    }
    .password-toggle {
        cursor: pointer;
        color: #a1a5b7;
        transition: color .2s;
    }
    .password-toggle:hover {
        color: var(--bs-primary);
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('togglePassword');
    const pwd = document.getElementById('password');
    if (toggle && pwd) {
        toggle.addEventListener('click', () => {
            const type = pwd.getAttribute('type') === 'password' ? 'text' : 'password';
            pwd.setAttribute('type', type);
            toggle.classList.toggle('fa-eye');
            toggle.classList.toggle('fa-eye-slash');
        });
    }
});
</script>
@endpush

@section('content')
<div class="d-flex flex-column justify-content-center align-items-center p-4 login-container">

    <!-- Login Card -->
    <div class="card login-card">
        <div class="card-body p-lg-10">

            <!-- Logo -->
            <div class="mb-10 text-center brand-logo">
                <a href="{{ url('/') }}">
                    <img alt="Kaamiyaabu Logo" src="{{ asset('assets/media/logos/logo-compact.svg') }}" />
                </a>
            </div>

            <div class="text-center mb-8">
                <h1 class="text-dark mb-1 fs-2">Sign In to Kaamiyaabu</h1>
                <p class="text-muted fw-normal fs-6">Enter your credentials to access your account.</p>
            </div>

            <!-- Global Errors -->
            @if ($errors->any())
                <div class="alert alert-danger mb-7 py-3 px-4">
                    <div class="d-flex flex-column">
                        @foreach ($errors->all() as $error)
                            <span class="small">{{ $error }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Form -->
            <form method="POST" action="{{ route('login') }}" novalidate>
                @csrf

                <!-- Email -->
                <div class="mb-6">
                    <label class="form-label fw-semibold text-dark fs-6">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required
                           class="form-control form-control-lg form-control-solid @error('email') is-invalid @enderror"
                           placeholder="you@example.com" />
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <div class="d-flex justify-content-between">
                        <label class="form-label fw-semibold text-dark fs-6">Password</label>
                        @if (Route::has('password.request'))
                            <a href="{{ route('password.request') }}" class="link-primary fs-6 fw-semibold">Forgot Password?</a>
                        @endif
                    </div>
                    <div class="position-relative">
                        <input type="password" name="password" id="password" autocomplete="current-password" required
                               class="form-control form-control-lg form-control-solid @error('password') is-invalid @enderror"
                               placeholder="••••••••" />
                        <span class="position-absolute top-50 end-0 translate-middle-y me-4">
                            <i class="fa fa-eye-slash fs-5 password-toggle" id="togglePassword"></i>
                        </span>
                    </div>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Remember Me -->
                <div class="d-flex flex-stack mb-7">
                    <div class="form-check form-check-custom form-check-solid">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} />
                        <label class="form-check-label text-muted" for="remember">Remember me</label>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">
                        Sign In
                    </button>
                </div>
            </form>

            <!-- Footer -->
            <div class="mt-10 fs-7 text-muted text-center">
                &copy; {{ date('Y') }} Kaamiyaabu. All rights reserved.
            </div>
        </div>
    </div>

</div>
@endsection
