@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        @keyframes float {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-20px);
            }
        }

        .hover-lift {
            transition: all 0.3s ease;
        }

        .hover-lift:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1) !important;
        }

        .dashboard-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .dashboard-card:hover {
            border-color: rgba(0, 0, 0, 0.1);
        }

        .dashboard-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .dashboard-icon i {
            font-size: 24px;
        }

        .features-list {
            border-left: 3px solid rgba(0, 0, 0, 0.1);
            padding-left: 15px;
        }

        .mini-stats-card {
            transition: all 0.3s ease;
        }

        .mini-stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-info) 100%);
        }

        .kendedes-card-header {
            background: rgb(251, 99, 64) !important;
        }

        .ken-arok-card-header {
            background: #007bff !important;
        }

        .hero-card-white {
            background: #ffffff !important;
            border: 1px solid #e9ecef;
        }

        .timeline-step {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-lg {
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-lg:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .floating-animation {
            animation: float 3s ease-in-out infinite;
        }

        .floating-animation-delayed {
            animation: float 3s ease-in-out infinite 0.5s;
        }

        .floating-animation-delayed-2 {
            animation: float 3s ease-in-out infinite 1s;
        }
    </style>

    <style>
        .hero-card-white {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .hero-card-white:hover {
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .font-weight-bolder {
            font-weight: 700 !important;
        }

        .hero-title {
            font-size: 2.5rem;
            line-height: 1.2;
        }

        .info-section {
            /* background: linear-gradient(135deg, #f8fafc 60%, #e9f7ef 100%); */
            border-radius: 18px;
            box-shadow: 0 2px 12px rgba(40, 167, 69, 0.06);
            /* border-left: 5px solid rgb(251, 99, 64); */
            /* padding: 1.5rem; */
        }

        .info-text {
            font-size: 1.13rem;
            color: #333;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .info-list {
            font-size: 1.08rem;
            color: #444;
            padding-left: 1.5rem;
        }

        .info-list li {
            margin-bottom: 8px;
        }

        .conclusion-text {
            font-size: 1.08rem;
            color: #333;
        }

        .app-showcase {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1.5rem 1rem;
            height: auto;
            min-height: 220px;
            gap: 1.5rem;
        }

        .app-item {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            gap: 20px;
            width: 100%;
            max-width: 350px;
            text-align: left;
        }

        .app-logo {
            background: #fff;
            border-radius: 50%;
            width: 110px;
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .kendedes-logo {
            box-shadow: 0 4px 16px rgba(255, 145, 0, 0.13);
            border: 4px solid #ff9100;
        }

        .kenarok-logo {
            box-shadow: 0 4px 16px rgba(0, 123, 255, 0.13);
            border: 4px solid #007bff;
        }

        .app-logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .app-name {
            font-weight: 900;
            font-size: 2rem;
            letter-spacing: 1px;
            flex-shrink: 0;
        }

        .kendedes-name {
            color: #ff9100;
            text-shadow: 0 2px 8px rgba(255, 145, 0, 0.08);
        }

        .kenarok-name {
            color: #007bff;
            text-shadow: 0 2px 8px rgba(0, 123, 255, 0.08);
        }

        /* Badge styling */
        .feature-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            font-size: 0.95rem;
        }

        .badge {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Background decorations */
        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            z-index: 1;
        }

        .bg-decoration-1 {
            width: 200px;
            height: 200px;
            background: #ffc107;
            opacity: 0.05;
            top: -100px;
            right: -100px;
        }

        .bg-decoration-2 {
            width: 150px;
            height: 150px;
            background: #007bff;
            opacity: 0.05;
            bottom: -75px;
            left: -75px;
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .hero-title {
                font-size: 2.2rem;
            }

            .app-name {
                font-size: 1.8rem;
            }
        }

        @media (max-width: 992px) {
            .hero-title {
                font-size: 2rem;
                text-align: center;
                margin-bottom: 1.5rem;
            }

            .info-section {
                margin-bottom: 2rem;
            }

            .feature-badges {
                justify-content: center;
                margin-top: 1rem;
            }

            .app-showcase {
                margin-top: 2rem;
                padding: 2rem 1rem;
            }

            .app-item {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .app-name {
                font-size: 1.6rem;
            }
        }

        @media (max-width: 600px) {
            .app-item {
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 8px !important;
                text-align: center !important;
                width: 100% !important;
                max-width: 100% !important;
            }
        }

        .info-list {
            font-size: 0.95rem;
            padding-left: 1rem;
        }

        .conclusion-text {
            font-size: 0.95rem;
        }

        .app-logo {
            width: 90px;
            height: 90px;
        }

        .app-logo img {
            width: 65px;
            height: 65px;
        }

        .app-name {
            font-size: 1.4rem;
        }

        .feature-badges {
            font-size: 0.82rem;
        }

        .badge {
            font-size: 0.78rem;
            padding: 0.35rem 0.7rem;
        }

        .app-showcase {
            gap: 1.2rem;
            min-height: 180px;
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem !important;
            }

            .hero-title {
                font-size: 1.5rem;
                line-height: 1.3;
            }

            .info-section {
                padding: 1.25rem;
            }

            .info-text {
                font-size: 0.89rem;
            }

            .info-list {
                font-size: 0.9rem;
            }

            .conclusion-text {
                font-size: 0.9rem;
            }

            .app-logo {
                width: 80px;
                height: 80px;
                border-width: 3px;
            }

            .app-logo img {
                width: 55px;
                height: 55px;
            }

            .app-name {
                font-size: 1rem;
                letter-spacing: 0.3px;
            }

            .feature-badges {
                font-size: 0.7rem;
            }

            .badge {
                font-size: 0.7rem;
                padding: 0.28rem 0.6rem;
            }

            .feature-badges {
                gap: 0.5rem;
            }

            .app-showcase {
                gap: 1rem;
                padding: 1.5rem 0.5rem;
                min-height: 160px;
            }

            .bg-decoration-1 {
                width: 120px;
                height: 120px;
                top: -60px;
                right: -60px;
            }

            .bg-decoration-2 {
                width: 100px;
                height: 100px;
                bottom: -50px;
                left: -50px;
            }
        }

        @media (max-width: 400px) {
            .hero-title {
                font-size: 1.1rem;
            }

            .app-name {
                font-size: 0.85rem;
            }

            .app-logo {
                width: 80px;
                height: 80px;
            }

            .app-logo img {
                width: 55px;
                height: 55px;
            }
        }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Home'])
    <div class="container-fluid py-4">

        <!-- Hero Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card hero-card-white border-0 overflow-hidden position-relative shadow-lg">
                    <div class="card-body p-5">
                        <div class="row align-items-center">
                            <div class="col-lg-9 px-4">
                                <h1 class="text-dark font-weight-bolder mb-3 hero-title">
                                    Selamat Datang di Platform <span class="text-warning">Ken Dedes Web</span>
                                </h1>
                                <div class="info-section mb-4">
                                    <div class="d-flex align-items-start mb-2">
                                        <div class="info-text">
                                            <span style="font-weight:bold;">Dua aplikasi, dua kekuatan</span> data yang
                                            saling melengkapi — hadir untuk mendukung <span style="font-weight:bold;">Sensus
                                                Ekonomi 2026</span> di <span style="font-weight:bold;">Jawa Timur</span>.
                                        </div>
                                    </div>
                                    <ul class="mb-2 info-list">
                                        <li><span style="font-weight:bold;">Ken Dedes Mobile</span>
                                            menghimpun data langsung dari lapangan — <span
                                                style="font-weight:bold;">Realtime</span>, kontekstual, dan akurat, menjadi
                                            fondasi awal yang kuat.</li>
                                        <li><span style="font-weight:bold;">Ken Arok</span>
                                            memverifikasi berbagai sumber <span style="font-weight:bold;">Big
                                                Data</span> seperti Snapwangi, dan Regsosek — memberikan gambaran
                                            makro yang luas, strategis, dan mendalam. Usaha yang terverifikasi akan
                                            dimasukkan kembali ke dalam database Ken Dedes Web.</li>
                                    </ul>
                                    <div class="conclusion-text">
                                        Bersama, keduanya membentuk ekosistem data yang komprehensif dan andal — sebagai
                                        landasan perencanaan, kebijakan, dan langkah nyata menuju <span
                                            style="font-weight:bold;">Sukses</span>nya <span
                                            style="font-weight:bold;">Sensus Ekonomi 2026</span>.
                                    </div>
                                </div>
                                <div class="feature-badges">
                                    <span class="badge bg-primary text-white px-3 py-2 rounded-pill">
                                        <i class="fas fa-chart-line me-1"></i> Real-time Data Tagging
                                    </span>
                                    <span class="badge bg-success text-white px-3 py-2 rounded-pill">
                                        <i class="fas fa-shield-alt me-1"></i> Big Data Integration (Snapwangi)
                                    </span>
                                    <span class="badge bg-info text-white px-3 py-2 rounded-pill">
                                        <i class="fas fa-users me-1"></i> Multi Mode Data Collection
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-3 text-center px-4">
                                <div class="app-showcase">
                                    <div class="app-item">
                                        <div class="app-logo kendedes-logo">
                                            <img src="/img/short_logo.png" alt="Kendedes" />
                                        </div>
                                        <div class="app-name kendedes-name">Ken Dedes<br>Mobile</div>
                                    </div>
                                    <div class="app-item">
                                        <div class="app-logo kenarok-logo">
                                            <img src="/img/kenarok icon.png" alt="Kenarok" />
                                        </div>
                                        <div class="app-name kenarok-name">Ken Arok</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Decorative background elements -->
                    <div class="position-absolute top-0 end-0 w-100 h-100 opacity-1">
                        <div class="bg-decoration bg-decoration-1"></div>
                        <div class="bg-decoration bg-decoration-2"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Cards -->
        <div class="row mb-4">
            <!-- Kendedes Dashboard Card -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card dashboard-card h-100 border-0 shadow-lg hover-lift">
                    <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center dashboard-card-bg-orange" style="min-height:140px; border-radius:1rem; position:relative; overflow:hidden;">
                        <div class="dashboard-card-icon mb-2" style="background:rgba(255,145,0,0.12); border-radius:50%; width:54px; height:54px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(255,145,0,0.08);">
                            <img src="/img/short_logo.png" alt="Kendedes" style="width:32px; height:32px; object-fit:contain;" />
                        </div>
                        <div class="dashboard-card-title text-dark fw-bold" style="font-size:1.2rem; letter-spacing:0.5px;">Ken Dedes Dashboard</div>
                        <div class="dashboard-card-subtitle mb-3 text-secondary text-center" style="font-size:0.98rem;">Dashboard Pendataan Sentra Ekonomi dan Tagging KDM</div>
                        <a href="/pasar-dashboard" class="btn btn-dark w-100 btn-lg mt-2">
                            <i class="fas fa-arrow-right me-2"></i>
                            Akses Dashboard Ken Dedes
                        </a>
                    </div>
                </div>
            </div>

            <!-- Ken Arok Dashboard Card -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card dashboard-card h-100 border-0 shadow-lg hover-lift">
                    <div class="card-body p-4 d-flex flex-column align-items-center justify-content-center dashboard-card-bg-blue" style="min-height:140px; border-radius:1rem; position:relative; overflow:hidden;">
                        <div class="dashboard-card-icon mb-2" style="background:rgba(0,123,255,0.12); border-radius:50%; width:54px; height:54px; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 8px rgba(0,123,255,0.08);">
                            <img src="/img/kenarok icon.png" alt="Kenarok" style="width:32px; height:32px; object-fit:contain;" />
                        </div>
                        <div class="dashboard-card-title text-dark fw-bold" style="font-size:1.2rem; letter-spacing:0.5px;">Ken Arok Dashboard</div>
                        <div class="dashboard-card-subtitle mb-3 text-secondary text-center" style="font-size:0.98rem;">Dashboard Hasil Verifikasi Big Data Snapwangi</div>
                        <a href="/kenarok-dashboard" class="btn btn-secondary w-100 btn-lg mt-2"
                            style="background-color: #34495e; border-color: #34495e;">
                            <i class="fas fa-arrow-right me-2"></i>
                            Akses Dashboard Ken Arok
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- <!-- Quick Stats Row -->
        <div class="row mb-4">
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card mini-stats-card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-uppercase font-weight-bold">System Status</p>
                                    <h5 class="font-weight-bolder text-success">Online</h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-success shadow text-center border-radius-md">
                                    <i class="fas fa-server text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card mini-stats-card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-uppercase font-weight-bold">Active Sessions</p>
                                    <h5 class="font-weight-bolder">1,234</h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-warning shadow text-center border-radius-md">
                                    <i class="fas fa-users text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card mini-stats-card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-uppercase font-weight-bold">Daily Operations</p>
                                    <h5 class="font-weight-bolder">+23%</h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                                    <i class="fas fa-chart-line text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-sm-6">
                <div class="card mini-stats-card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-uppercase font-weight-bold">Response Time</p>
                                    <h5 class="font-weight-bolder text-success">0.23s</h5>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-dark shadow text-center border-radius-md">
                                    <i class="fas fa-clock text-lg opacity-10"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0 p-3">
                        <h6 class="mb-0">Recent Activity</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="timeline timeline-one-side">
                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="fas fa-user text-success text-gradient"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">New user registered</h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">2 minutes ago</p>
                                </div>
                            </div>
                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="fas fa-database text-info text-gradient"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">Ken Arok data processing completed
                                    </h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">15 minutes ago</p>
                                </div>
                            </div>
                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="fas fa-chart-bar text-warning text-gradient"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">Monthly report generated</h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">1 hour ago</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        @include('layouts.footers.auth.footer')
    </div>
@endsection

@push('js')
    <script>
        // Add any additional JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Dashboard card hover effects
            const dashboardCards = document.querySelectorAll('.dashboard-card');
            dashboardCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Stats cards hover effects
            const statsCards = document.querySelectorAll('.mini-stats-card');
            statsCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
@endpush
