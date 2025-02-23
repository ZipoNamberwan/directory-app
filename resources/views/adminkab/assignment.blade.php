@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')

@endsection

@section('content')
@include('layouts.navbars.auth.topnav', ['title' => 'Assignment'])
<div class="container-fluid py-4">

    <div class="row">
        <div class="col-lg-12 mb-lg-0 mb-4">
            <div class="card z-index-2 h-100">
                <div class="card-header pb-0 pt-3 bg-transparent">
                    <h6 class="text-capitalize">Assignment Direktori Usaha Sampai Level SLS</h6>
                    <p class="text-sm mb-0">
                        <span>Menu ini digunakan untuk melakukan assignment <strong>direktori usaha sampai level SLS</strong></span>
                    </p>
                    <p class="text-sm mb-0">
                        <span>Karena besarnya data yang perlu diproses, proses download template dan upload assignment akan melalui proses antrian. Sehingga data assignment mungkin akan diproses beberapa waktu setelah upload</span>
                    </p>
                </div>
                <div class="card-body p-3">

                    <div class="row">
                        <div class="col-md-6 col-sm-12 p-2">
                            <div class="bg-light p-4 rounded">
                                <h6 class="mb-3 d-flex align-items-center">
                                    <span class="badge bg-info me-2">1</span>
                                    Generate Template
                                </h6>
                                <p class="text-muted small mb-3">
                                    Menu ini digunakan untuk membuat template assignment. Proses pembuatan template akan masuk antrian, sehingga mungkin akan memakan waktu beberapa saat.
                                </p>
                                <p class="text-muted small mb-3">
                                    Tombol status digunakan untuk melihat status proses pembuatan template.
                                </p>
                                @livewire('export')
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12 p-2">
                            <div class="bg-light p-4 rounded">
                                <h6 class="mb-3 d-flex align-items-center">
                                    <span class="badge bg-success me-2">2</span>
                                    Upload Template
                                </h6>
                                <p class="text-muted small mb-3">
                                    Menu ini digunakan untuk mengupload template yang telah diisi. Karena data assignment yang diproses banyak, sehingga proses ini akan memakan beberapa waktu.
                                </p>
                                <p class="text-muted small mb-3">
                                    Tombol status digunakan untuk melihat status assignment.
                                </p>
                                @livewire('import')
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusDialog" tabindex="-1" role="dialog" aria-labelledby="statusDialogLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            @livewire('status-dialog')
        </div>
    </div>

    @include('layouts.footers.auth.footer')
</div>
@endsection

@push('js')

@endpush