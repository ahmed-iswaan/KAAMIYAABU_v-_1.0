@extends('layouts.auth')

@section('content')
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">

            <!-- Aside panel -->
            <div class="d-flex flex-column flex-lg-row-auto bg-primary w-xl-600px position-xl-relative">
                <div class="d-flex flex-column position-xl-fixed top-0 bottom-0 w-xl-600px scroll-y">
                    <div class="d-flex flex-row-fluid flex-column text-center p-5 p-lg-10 pt-lg-20">
                        <a href="{{ url('/') }}" class="py-2 py-lg-20">
                            <img alt="Logo"
                                 src="{{ asset('assets/media/logos/logo-ellipse.svg') }}"
                                 class="h-60px h-lg-70px" />
                        </a>
                        <h1 class="d-none d-lg-block fw-bold text-white fs-2qx pb-4 pb-md-8">
                            Welcome to CouncilDesk
                        </h1>
                        <p class="d-none d-lg-block fw-light fs-3 text-white px-10">
                            The easiest way to manage requests, approvals, and team workflows in one place.
                        </p>
                    </div>
                    <div class="d-none d-lg-block flex-row-auto bgi-no-repeat bgi-position-x-center
                                bgi-size-contain bgi-position-y-bottom min-h-100px min-h-lg-350px"
                         style="background-image: url({{ asset('assets/media/illustrations/sigma-1/17.png') }})">
                    </div>
                </div>
            </div>

            <!-- Sign-in card -->
            <div class="d-flex flex-column flex-lg-row-fluid py-10">
                <div class="d-flex flex-center flex-column flex-column-fluid">
                    <div class="card shadow-sm w-lg-450px">
                        <div class="card-body p-8 p-lg-12">
                            <h2 class="text-center text-dark mb-8">Sign In to CouncilDesk</h2>

                            <form method="POST" action="{{ route('login') }}" novalidate>
                                @csrf

                                {{-- Email --}}
                                <div class="mb-6">
                                    <label class="form-label fw-semibold text-dark">Email Address</label>
                                    <input type="email"
                                           name="email"
                                           value="{{ old('email') }}"
                                           autocomplete="email"
                                           class="form-control form-control-lg form-control-solid @error('email') is-invalid @enderror"
                                           placeholder="you@example.com" />
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>


                            <div class="mb-4 position-relative">
                                <label class="form-label fw-semibold text-dark mb-1">Password</label>
                                <div class="input-group input-group-solid">
                                    <input 
                                        type="password"
                                        name="password"
                                        id="password"
                                        autocomplete="current-password"
                                        class="form-control form-control-lg form-control-solid @error('password') is-invalid @enderror"
                                        placeholder="••••••••" />

                                    <span class="input-group-text bg-transparent border-0 p-0 position-absolute end-0 top-50 translate-middle-y me-3">
                                        <i 
                                            class="fa fa-eye-slash fs-5 text-muted" 
                                            id="togglePassword" 
                                            style="cursor: pointer;"
                                            aria-label="Toggle password visibility">
                                        </i>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>


                                {{-- Remember Me --}}
                                <div class="form-check form-check-solid mb-6">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                           {{ old('remember') ? 'checked' : '' }} />
                                    <label class="form-check-label" for="remember">
                                        Remember me
                                    </label>
                                </div>

                                {{-- Submit --}}
                                <div class="d-grid mb-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        Sign In
                                    </button>
                                </div>

       
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="d-flex flex-center flex-wrap fs-7 text-muted py-6">
                    &copy; 2025 CouncilDesk v1.0
                </div>
            </div>

        </div>
    </div>

    
@endsection
