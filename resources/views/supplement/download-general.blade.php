@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Download Project Suplemen'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h4 class="text-capitalize">Download Project Suplemen SW Maps</h4>
                </div>
                <p class="text-sm">Menu ini digunakan untuk mendownload project suplemen SW Maps.</p>
            </div>
            <div class="card-body pt-1">
                <form id="formupdate" autocomplete="off" method="post" action="/suplemen/download" class="needs-validation"
                    enctype="multipart/form-data" novalidate>
                    @csrf
                    <button onclick="download()" class="btn btn-success mt-3" id="submitBtn">Download Project Android</button>
                </form>
                <p class="text-sm">Untuk IOs bisa download <a href="https://s.bps.go.id/swmaps-ios">di sini.</a></p>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>

        <script>
            function download() {
                event.preventDefault();
                document.getElementById('formupdate').submit();
            }
        </script>
    @endpush
@endsection
