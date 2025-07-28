@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        iframe {
            width: 100%;
            height: 800px;
            border: none;
            z-index: 10;
        }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Kenarok'])
    <div class="container-fluid py-4">

        <div class="card">
            <div class="card-header">
                <h4>Dashboard Ken Arok</h4>
            </div>
            <div class="card-body pt-0">
                <div class="mb-3 text-secondary" style="font-size:1rem;">
                    <i class="fas fa-external-link-alt me-1"></i>
                    <a href="https://lookerstudio.google.com/embed/reporting/f7bfefdb-2a57-4c2c-92ed-6a6fdcb809b3/page/ToAQF" target="_blank" rel="noopener" style="text-decoration:underline;">Buka dashboard di tab baru</a>
                </div>
                <iframe width="600" height="443"
                    src="https://lookerstudio.google.com/embed/reporting/f7bfefdb-2a57-4c2c-92ed-6a6fdcb809b3/page/ToAQF"
                    frameborder="0" style="border:0" allowfullscreen
                    sandbox="allow-storage-access-by-user-activation allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox">
                </iframe>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>
@endsection

@push('js')
@endpush
