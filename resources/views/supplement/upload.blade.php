@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Upload Suplemen'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h4 class="text-capitalize">Upload Suplemen</h4>
                </div>
            </div>
            <div class="card-body pt-1">
                
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>

    @endpush
@endsection
