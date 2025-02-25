@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Unduh'])
    <div class="container-fluid py-4">

        <div class="row">
            <div class="col-lg-12 mb-lg-0 mb-4">
                <div class="card z-index-2 h-100">
                    <div class="card-header pb-0 pt-3">
                        <h4 class="text-capitalize">Unduh Raw Data</h4>
                        <p class="text-muted small mb-3">
                            Menu ini digunakan untuk mengunduh <strong>raw data direktori usaha</strong>. Karena besarnya
                            data yang perlu diproses, pengunduhan akan dilakukan melalui antrian, sehingga data mungkin baru
                            tersedia setelah beberapa waktu. Perkiraan waktu pemrosesan adalah sekitar 1-5 detik per 1.000
                            record.
                        </p>
                        <p class="text-muted small mb-3">
                            Proses unduh akan berjalan di <strong>background</strong>, sehingga Anda dapat menutup tab ini
                            <strong>tanpa
                                kehilangan progres</strong>. Untuk memantau status pemrosesan dan mengunduh file setelah
                            siap, gunakan tombol <strong>"Status"</strong>.
                        </p>
                    </div>
                    <div class="card-body p-3">

                        <div class="row">
                            <div class="col-md-6 col-sm-12 p-2">
                                <div class="bg-light p-4 rounded">
                                    <h6 class="mb-3 d-flex align-items-center">
                                        <span class="badge bg-success me-2">*</span>
                                        Unduh Direktori Sampai Level SLS
                                    </h6>

                                    @livewire('download', ['type' => 'sls', 'color' => 'success'])
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12 p-2">
                                <div class="bg-light p-4 rounded">
                                    <h6 class="mb-3 d-flex align-items-center">
                                        <span class="badge bg-danger me-2">*</span>
                                        Unduh Direktori Tidak Sampai Level SLS
                                    </h6>
                                    @livewire('download', ['type' => 'non-sls', 'color' => 'danger'])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="statusDialog" tabindex="-1" role="dialog" aria-labelledby="statusDialogLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                @livewire('status-dialog')
            </div>
        </div>

        @include('layouts.footers.auth.footer')
    </div>
@endsection

@push('js')
@endpush
