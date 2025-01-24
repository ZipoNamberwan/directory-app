@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
<link href="/vendor/fontawesome/css/all.min.css" rel="stylesheet">

@endsection

@section('content')
@include('layouts.navbars.auth.topnav', ['title' => 'Assignment'])
<div class="container-fluid py-4">

    <div class="row">
        <div class="col-lg-12 mb-lg-0 mb-4">
            <div class="card z-index-2 h-100">
                <div class="card-header pb-0 pt-3 bg-transparent">
                    <h6 class="text-capitalize">Assignment Direktori Usaha</h6>
                    <p class="text-sm mb-0">
                        <span>Menu ini digunakan untuk melakukan assignment direktori usaha</span>
                    </p>
                    <p class="text-sm mb-0">
                        <span>Karena besarnya data yang perlu diproses, proses download template dan upload assignment akan melalui proses antrian. Sehingga data assignment mungkin akan diproses beberapa waktu setelah upload</span>
                    </p>
                </div>
                <div class="card-body p-3">

                    <div class="row">
                        <div class="col-md-4 col-sm-12 p-2">
                            <div class="bg-light p-4 rounded">
                                <h6 class="mb-3 d-flex align-items-center">
                                    <span class="badge bg-info me-2">1</span>
                                    Generate Template
                                </h6>
                                <p class="text-muted small mb-3">
                                    Menu ini digunakan untuk membuat template assignment. Proses pembuatan template akan masuk antrian, sehingga mungkin akan memakan waktu beberapa saat
                                </p>
                                <p class="text-muted small mb-3">
                                    Tombol status digunakan untuk melihat status proses pembuatan template
                                </p>
                                @livewire('export')
                                @livewire('import')
                                <!-- <button onclick="generateTemplate()" class="btn btn-info">
                                    <i class="fas fa-play me-2"></i>
                                    Buat
                                </button>
                                <button onclick="generateTemplate()" class="btn btn-outline-info">
                                    <i class="fas fa-circle-info me-2"></i>
                                    Status
                                </button> -->
                            </div>
                        </div>
                        <!-- <div class="col-md-4 col-sm-12 p-2">
                            <div class="bg-light p-4 rounded">
                                <h6 class="mb-3 d-flex align-items-center">
                                    <span class="badge bg-primary me-2">2</span>
                                    Unduh Template
                                </h6>
                                <p class="text-muted small mb-3">
                                    Menu ini digunakan untuk mengunduh template yang telah selesai diproses. Tombol hanya akan aktif jika proses pembuatan template telah selesai.
                                </p>
                                <button onclick="generateTemplate()" class="btn btn-primary">
                                    <i class="fas fa-download me-2"></i>
                                    Unduh
                                </button>
                            </div>
                        </div> -->
                        <div class="col-md-4 col-sm-12 p-2">
                            <div class="bg-light p-4 rounded">
                                <h6 class="mb-3 d-flex align-items-center">
                                    <span class="badge bg-success me-2">2</span>
                                    Upload Template
                                </h6>
                                <p class="text-muted small mb-3">
                                    Menu ini digunakan untuk mengupload template yang telah diisi. Karena data assignment yang diproses banyak, sehingga proses ini akan memakan beberapa waktu.
                                </p>
                                <p class="text-muted small mb-3">
                                    Tombol status digunakan untuk melihat status assignment
                                </p>
                                <div class="input-group">
                                    <input type="file" class="form-control form-control-lg" id="fileUpload" accept=".csv,.xlsx" onchange="handleFileUpload(event)">
                                    <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('fileUpload').click()">
                                        <i class="fas fa-upload me-2"></i>
                                        Choose File
                                    </button>
                                    <button onclick="generateTemplate()" class="btn btn-outline-success">
                                        <i class="fas fa-circle-success me-2"></i>
                                        Status
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('layouts.footers.auth.footer')
</div>
@endsection

@push('js')

@endpush