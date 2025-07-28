@extends('layouts.app')

@section('css')
    <style>
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
    </style>
@endsection

@section('content')
    <main class="main-content  mt-0">
        <section>
            <div class="page-header min-vh-100">
                <div class="container">
                    <div class="row">
                        <div class="col-xl-4 col-lg-5 col-md-7 d-flex flex-column mx-lg-0 mx-auto">
                            <div class="d-flex justify-content-center mb-4">
                                <img style="max-height: 3.5rem !important;" src="/img/full_logo.png"
                                    class="navbar-brand-img h-100" alt="main_logo">
                            </div>
                            <div class="card card-plain">
                                <div class="card-body">
                                    <a href="{{$redirectUrl}}" class="login-button w-100 text-decoration-none">
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
                                <hr class="bg-secondary">
                                <span
                                    class="position-absolute top-50 start-50 translate-middle px-3 bg-white text-secondary">Atau</span>
                            </div>
                            <div class="card card-plain">
                                <div class="card-header pb-0 text-center">
                                    <h6 class="font-weight-bolder">Log In</h6>
                                    <p class="text-sm mb-0">Gunakan email dan passsword untuk log in</p>
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
                            {{-- url('https://raw.githubusercontent.com/creativetimofficial/public-assets/master/argon-dashboard-pro/assets/img/signin-ill.jpg'); --}}
                            <div class="position-relative bg-gradient-primary h-100 m-3 px-7 border-radius-lg d-flex flex-column justify-content-center overflow-hidden"
                                style="background-image: url('{{ asset('img/kendedes kenarok.jpg') }}');
              background-size: cover;">
                                <span class="mask bg-gradient-primary opacity-6"></span>
                                <h1 class="mt-5 text-white font-weight-bolder position-relative">"Ken Dedes"</h1>
                                <h4 class="text-white position-relative">Kendali Direktori Ekonomi</h4>
                                <h4 class="text-white position-relative">Di Level Satuan Lingkungan Setempat Terkecil</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
@endsection
