@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />

@endsection

@section('content')
@include('layouts.navbars.auth.topnav', ['title' => 'Pemutakhiran'])
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header pb-0">
            <div class="d-flex align-items-center">
                <p class="mb-0">Edit Profile</p>
                <button class="btn btn-primary btn-sm ms-auto">Settings</button>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 mt-2">
                    <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                    <select id="subdistrict" name="subdistrict" class="form-control" data-toggle="select" name="subdistrict" required>
                        <option value="0" disabled selected> -- Pilih Kecamatan -- </option>
                    </select>
                </div>
                <div class="col-md-4 mt-2">
                    <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                    <select id="village" name="village" class="form-control" data-toggle="select" name="village"></select>
                </div>
                <div id="bs_div" class="col-md-4 mt-2 mb-4">
                    <label class="form-control-label">Blok Sensus <span class="text-danger">*</span></label>
                    <select id="bs" name="bs" class="form-control" data-toggle="select"></select>
                </div>
            </div>

            <div id="samplelist">

            </div>
        </div>
    </div>
    @include('layouts.footers.auth.footer')
</div>

@push('js')
<script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="/vendor/select2/select2.min.js"></script>

<script>
    $('#subdistrict').select2({
        placeholder: 'Select an option'
    });
</script>
@endpush

@endsection