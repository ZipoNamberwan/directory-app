@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
<link href="/assets/css/app.css" rel="stylesheet" />
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
<link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
@include('layouts.navbars.auth.topnav', ['title' => 'Assignment Pasar'])
<div class="container-fluid py-4">

    @if (session('success-upload'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <span class="alert-icon"><i class="ni ni-like-2"></i></span>
        <span class="alert-text"><strong>Success!</strong> {{ session('success-upload') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if (session('failed-upload'))
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <span class="alert-icon"><i class="ni ni-dislike-2"></i></span>
        <span class="alert-text">{{ session('failed-upload') }}</span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <div class="card z-index-2 h-100">
        <div class="card-header pb-0 pt-3 bg-transparent">
            <h6 class="text-capitalize">Assignment Pasar</h6>
        </div>
        <div class="card-body p-3">

            <div class="row">
                <div class="col-md-6 col-sm-12 p-2">
                    <div class="bg-light p-4 rounded">
                        <h6 class="mb-3 d-flex align-items-center">
                            <span class="badge bg-info me-2">1</span>
                            Download Template
                        </h6>
                        <p class="text-muted small mb-3">
                            Menu ini digunakan untuk mengunduh template assignment pasar.
                        </p>
                        <form id="formupdate" autocomplete="off" method="post" action="/pasar-assignment/download" class="needs-validation"
                            enctype="multipart/form-data" novalidate>
                            @csrf
                            <button class="btn btn-info mt-3" id="submit" type="submit">Download</button>
                        </form>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12 p-2">
                    <div class="bg-light p-4 rounded">
                        <h6 class="mb-3 d-flex align-items-center">
                            <span class="badge bg-success me-2">2</span>
                            Upload
                        </h6>
                        <form id="formupdate" autocomplete="off" method="post" action="/pasar-assignment/upload" class="needs-validation"
                            enctype="multipart/form-data" novalidate>
                            @csrf
                            <label class="form-control-label">File Assignment <span class="text-danger">*</span></label>
                            <input id="file" name="file" type="file" class="form-control" accept=".xlsx,.csv">
                            @error('file')
                            <div class="error-feedback">
                                {{ $message }}
                            </div>
                            @enderror
                            <button class="btn btn-success mt-3" id="submit" type="submit">Upload</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="/vendor/select2/select2.min.js"></script>
<script src="/vendor/datatables/dataTables.min.js"></script>
<script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

<script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
<script src="/vendor/datatables/dataTables.responsive.min.js"></script>

@endpush