@extends('layouts.app')

@section('css')
    <style>
        /* Enhanced Indonesian flag decoration */
        .flag-decoration {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 12px;
            z-index: 1000;
            background: linear-gradient(to right, #dc2626 50%, #ffffff 50%);
            box-shadow: 0 2px 8px rgba(220, 38, 38, 0.3);
        }

        .flag-decoration::after {
            content: '';
            position: absolute;
            top: 12px;
            left: 0;
            right: 0;
            height: 12px;
            background: linear-gradient(to right, #dc2626 50%, #ffffff 50%);
        }

        /* Independence Day Badge inside right panel */
        .independence-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
            color: white;
            padding: 10px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: bold;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            z-index: 10;
            animation: pulse 3s ease-in-out infinite;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .independence-badge::before {
            content: '🇮🇩';
            margin-right: 8px;
            font-size: 14px;
        }

        @keyframes pulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        /* Floating Indonesian themed elements */
        .float-decoration {
            position: fixed;
            z-index: 10;
            opacity: 0.4;
            animation: float 8s ease-in-out infinite;
        }

        .float-decoration:nth-child(1) {
            top: 20%;
            left: 10%;
            color: #dc2626;
            font-size: 24px;
            animation-delay: 0s;
        }

        .float-decoration:nth-child(2) {
            top: 30%;
            right: 15%;
            color: #dc2626;
            font-size: 20px;
            animation-delay: -2s;
        }

        .float-decoration:nth-child(3) {
            bottom: 30%;
            left: 8%;
            color: #dc2626;
            font-size: 22px;
            animation-delay: -4s;
        }

        @keyframes float {

            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }

            25% {
                transform: translateY(-15px) rotate(90deg);
            }

            50% {
                transform: translateY(-5px) rotate(180deg);
            }

            75% {
                transform: translateY(-20px) rotate(270deg);
            }
        }

        /* Keep original Majapahit login button styling */
        .login-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background: linear-gradient(90deg, #4a3b8f 0%, #2871d8 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-decoration: none;
            text-align: center;
        }

        .login-button:hover {
            color: white !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            background: linear-gradient(90deg, #5842a8 0%, #318aff 100%);
        }

        .login-button:active {
            transform: translateY(1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .login-content {
            display: flex;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .login-icon {
            width: 24px;
            height: 24px;
            margin-right: 12px;
            border-radius: 50%;
            background: white;
            padding: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        /* Enhanced independence greeting */
        .independence-greeting {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.1) 0%, rgba(255, 255, 255, 0.95) 50%, rgba(220, 38, 38, 0.1) 100%);
            border: 2px solid rgba(220, 38, 38, 0.2);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.1);
            position: relative;
            overflow: hidden;
        }

        .independence-greeting::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(220, 38, 38, 0.1), transparent);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0% {
                left: -100%;
            }

            50% {
                left: 100%;
            }

            100% {
                left: 100%;
            }
        }

        .independence-greeting h6 {
            color: #dc2626;
            margin: 0 0 8px 0;
            font-size: 16px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        .independence-greeting p {
            color: #7f1d1d;
            margin: 0;
            font-size: 13px;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        /* Independence Day Logo styling */
        .independence-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin: 0 auto 12px auto;
            display: block;
            filter: drop-shadow(0 2px 4px rgba(220, 38, 38, 0.3));
            position: relative;
            z-index: 2;
        }

        /* Enhanced form styling */
        .form-control:focus {
            border-color: #dc2626;
            box-shadow: 0 0 0 0.2rem rgba(220, 38, 38, 0.25);
        }

        .btn-primary {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            border: none;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #b91c1c 0%, #7f1d1d 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(220, 38, 38, 0.3);
        }

        /* Enhanced cards */
        .card-plain {
            border: 1px solid rgba(220, 38, 38, 0.1);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.08);
            transition: all 0.3s ease;
        }

        .card-plain:hover {
            box-shadow: 0 8px 24px rgba(220, 38, 38, 0.15);
            transform: translateY(-2px);
        }

        /* Enhanced divider */
        .enhanced-divider {
            background: linear-gradient(to right, transparent, #dc2626, #ffffff, #dc2626, transparent);
            height: 2px;
            border: none;
        }

        /* Indonesian red background for Ken Dedes panel with image visibility */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 50%, #7f1d1d 100%) !important;
        }

        /* Independence celebration elements */
        .celebration-element {
            position: absolute;
            color: rgba(255, 255, 255, 0.3);
            font-size: 20px;
            animation: celebrate 4s ease-in-out infinite;
        }

        .celebration-element:nth-child(1) {
            top: 10%;
            right: 10%;
            animation-delay: 0s;
        }

        .celebration-element:nth-child(2) {
            bottom: 20%;
            right: 15%;
            animation-delay: -1s;
        }

        .celebration-element:nth-child(3) {
            top: 60%;
            right: 5%;
            animation-delay: -2s;
        }

        @keyframes celebrate {

            0%,
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 0.3;
            }

            50% {
                transform: scale(1.2) rotate(180deg);
                opacity: 0.6;
            }
        }

        /* HUT Logo in right panel */
        .right-panel-hut-logo {
            position: absolute;
            top: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.3));
            z-index: 10;
            opacity: 0.9;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .flag-decoration {
                height: 8px;
            }

            .flag-decoration::after {
                height: 8px;
            }

            .independence-badge {
                font-size: 11px;
                padding: 8px 12px;
                top: 32px;
                right: 15px;
            }

            .float-decoration {
                font-size: 18px !important;
            }

            .independence-greeting h6 {
                font-size: 14px;
            }

            .independence-greeting p {
                font-size: 12px;
            }

            .independence-logo {
                width: 40px;
                height: 40px;
                margin-bottom: 8px;
            }

            .right-panel-hut-logo {
                width: 45px;
                height: 45px;
                top: 15px;
                left: 15px;
            }
        }
    </style>
@endsection

@section('content')
    <!-- Enhanced Indonesian Flag Decoration -->
    {{-- <div class="flag-decoration"></div> --}}

    <main class="main-content mt-0">
        <section>
            <div class="page-header min-vh-100">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column mx-lg-0 mx-auto">
                            <div class="d-flex justify-content-center mb-4">
                                <img style="max-height: 3.5rem !important;" src="/img/full_logo.png"
                                    class="navbar-brand-img h-100" alt="main_logo">
                            </div>

                            <!-- Enhanced Independence Day Greeting with HUT Logo -->
                            <div class="independence-greeting">
                                <img src="/img/hut.png" alt="HUT RI ke-80" class="independence-logo">
                                <h6>Dirgahayu Hari Kemerdekaan Indonesia!</h6>
                                <p><strong>17 Agustus 2025 - HUT Republik Indonesia ke-80</strong></p>
                                {{-- <p style="margin-top: 4px; font-style: italic;">"Merdeka atau Mati!"</p> --}}
                            </div>

                            <div class="card card-plain">
                                <div class="card-body">
                                    <a href="{{ $redirectUrl }}" class="login-button w-100 text-decoration-none">
                                        <span class="login-content">
                                            <span class="login-icon">
                                                <img src="/img/logo-majapahit.png" alt="Majapahit Logo">
                                            </span>
                                            <span>Login with <strong>Majapahit</strong></span>
                                        </span>
                                    </a>
                                </div>
                            </div>
                            <div class="position-relative my-4">
                                <hr class="enhanced-divider">
                                <span class="position-absolute top-50 start-50 translate-middle px-3 bg-white"
                                    style="color: #dc2626; font-weight: 600;">Atau</span>
                            </div>
                            <div class="card card-plain">
                                <div class="card-header pb-0 text-center">
                                    <h6 class="font-weight-bolder" style="color: #dc2626;">Log In</h6>
                                    <p class="text-sm mb-0">Gunakan email dan password untuk log in</p>
                                </div>
                                <div class="card-body">
                                    <form role="form" method="POST" action="{{ route('login.perform') }}">
                                        @csrf
                                        @method('post')
                                        <div class="flex flex-col mb-3">
                                            <input type="email" name="email" class="form-control form-control-lg"
                                                placeholder="Email" value="{{ old('email') }}" aria-label="Email">
                                            @error('email')
                                                <p class="text-danger text-xs pt-1"> {{ $message }} </p>
                                            @enderror
                                        </div>
                                        <div class="flex flex-col mb-3">
                                            <input type="password" name="password" class="form-control form-control-lg"
                                                aria-label="Password" placeholder="Password">
                                            @error('password')
                                                <p class="text-danger text-xs pt-1"> {{ $message }} </p>
                                            @enderror
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" name="remember" type="checkbox" id="rememberMe">
                                            <label class="form-check-label" for="rememberMe">Remember me</label>
                                        </div>
                                        <div class="text-center">
                                            <button type="submit"
                                                class="btn btn-lg btn-primary btn-lg w-100 mt-4 mb-0">Sign in</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="card-footer text-center pt-0 px-lg-2 px-1">
                                    <p class="mb-1 text-sm mx-auto">
                                        Jika lupa password, silakan menghubungi admin kab/kota masing-masing
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div
                            class="col-6 d-lg-flex d-none h-100 my-auto pe-0 position-absolute top-0 end-0 text-center justify-content-center flex-column">
                            <div class="position-relative h-100 m-3 px-7 border-radius-lg d-flex flex-column justify-content-center overflow-hidden"
                                style="background: linear-gradient(135deg, rgba(220, 38, 38, 0.5), rgba(153, 27, 27, 0.5), rgba(127, 29, 29, 0.5)), url('{{ asset('img/kendedes kenarok.jpg') }}');background-size: cover;">

                                <!-- HUT Logo in right panel -->
                                <img src="/img/hut.png" alt="HUT RI ke-80" class="right-panel-hut-logo">

                                <!-- Independence Day Badge inside right panel -->
                                <div class="independence-badge">
                                    HUT RI ke-80 - Merdeka!
                                </div>

                                <div class="position-relative">
                                    <div class="mb-3">
                                        <h5 class="text-white"
                                            style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">
                                            HUT Republik Indonesia ke-80
                                        </h5>
                                        <p class="text-white"
                                            style="font-size: 14px; opacity: 0.9; margin: 0; font-weight: 500;">
                                            17 Agustus 2025
                                        </p>
                                        <div class="mt-1">
                                            <p class="text-white"
                                                style="font-size: 15px; opacity: 0.95; font-weight: 500; font-style: italic;">
                                                "Bersatu Berdaulat, Rakyat Sejahtera, Indonesia Maju"
                                            </p>
                                        </div>
                                    </div>
                                    <h1 class="mt-4 text-white font-weight-bolder">"Ken Dedes"</h1>
                                    <h4 class="text-white">Kendali Direktori Ekonomi</h4>
                                    <h4 class="text-white mb-4">Di Level Satuan Lingkungan Setempat Terkecil</h4>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection