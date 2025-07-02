@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Donwload Report'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">

        @if (session('success-edit') || session('success-create'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="alert-icon"><i class="ni ni-like-2"></i></span>
                <span class="alert-text"><strong>Success!</strong> {{ session('success-create') }}
                    {{ session('success-edit') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('success-delete') || session('error-delete'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <span class="alert-icon"><i class="ni ni-dislike-2"></i></span>
                <span class="alert-text">{{ session('success-delete') }} {{ session('error-delete') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header pb-0">
                <h4 class="text-capitalize">Download Report</h4>
                <p class="text-sm mb-0">
                    <span>Menu ini digunakan untuk mendownload report dalam bentuk .csv</span>
                </p>
            </div>
            <div class="card-body pt-1">
                <form id="formupdate" autocomplete="off" method="post" action="/pasar-dashboard/download"
                    class="needs-validation" enctype="multipart/form-data" novalidate>
                    @csrf
                    @method('POST')
                    <div class="row mt-3">
                        <div class="col-md-4">
                            <label class="form-control-label">Jenis Report <span class="text-danger">*</span></label>
                            <select style="width: 100%;" id="report" name="report" class="form-control"
                                data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Jenis Report -- </option>
                                <option value="regency" {{ old('report') == 'regency' ? 'selected' : '' }}>
                                    Report Jumlah Usaha Sentra Ekonomi Berdasarkan Kabupaten/Kota
                                </option>
                                <option value="supplement"
                                    {{ old('report') == 'total_supplement_regency' ? 'selected' : '' }}>
                                    Report Jumlah Usaha Suplemen Berdasarkan Kabupaten/Kota
                                </option>
                                <option value="market" {{ old('report') == 'market' ? 'selected' : '' }}>
                                    Report Jumlah Usaha Berdasarkan Sentra Ekonomi
                                </option>
                                <option value="user" {{ old('report') == 'user' ? 'selected' : '' }}>
                                    Report Jumlah Usaha Berdasarkan Petugas
                                </option>
                            </select>
                            @error('report')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row mt-3 {{ old('report') !== 'regency' ? 'd-none' : '' }}" id="marketTypeContainer">
                        <div class="col-md-4">
                            <label class="form-control-label">Pilih Tipe Sentra Ekonomi <span
                                    class="text-danger">*</span></label>
                            <select id="marketType" name="marketType" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Tipe Sentra Ekonomi -- </option>
                                <option value="all" {{ old('marketType') == 'all' ? 'selected' : '' }}> Semua </option>
                                @foreach ($marketTypes as $marketType)
                                    <option value="{{ $marketType->id }}"
                                        {{ old('marketType') == $marketType->id ? 'selected' : '' }}>
                                        {{ $marketType->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('marketType')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    {{-- <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-control-label">Tanggal <span class="text-danger">*</span></label>
                            <input class="form-control" type="date" id="date" name="date" onfocusout="defocused(this)">
                        </div>
                    </div> --}}
                    <button class="btn btn-primary mt-3" id="submit" type="submit">Download</button>
                </form>
            </div>
        </div>

        <div class="card mt-2">
            <div class="card-header pb-0">
                <h6 class="text-capitalize">Status Download</h6>
            </div>
            <div class="card-body">
                <div>
                    <button id="refresh" onclick="refresh()" class="btn btn-primary btn-sm p-2">Refresh</button>
                </div>
                <table id="statusTable" class="align-items-center mb-0 text-sm">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">File</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Jenis Report</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Dibuat Oleh</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Status</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Dibuat pada</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Pesan</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>
        <script src="/vendor/datatables/dataTables.min.js"></script>
        <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

        <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
        <script src="/vendor/datatables/dataTables.responsive.min.js"></script>
        <script src="/vendor/sweetalert2/sweetalert2.js"></script>

        <script>
            [{
                    selector: '#report',
                    placeholder: 'Pilih Jenis Report',
                },
                {
                    selector: '#marketType',
                    placeholder: 'Pilih Tipe Sentra Ekonomi',
                },
            ].forEach(config => {
                $(config.selector).select2({
                    placeholder: config.placeholder,
                    allowClear: true,
                });
            });

            const eventHandlers = {
                '#report': () => {
                    toggleMarketType()
                },
            };

            Object.entries(eventHandlers).forEach(([selector, handler]) => {
                $(selector).on('change', handler);
            });

            function toggleMarketType() {
                const selectedReport = $('#report').val();
                const marketTypeContainer = $('#marketTypeContainer');

                if (selectedReport === 'regency') {
                    marketTypeContainer.removeClass('d-none');
                } else {
                    marketTypeContainer.addClass('d-none');
                    $('#marketType').val(null).trigger('change'); // Clear selection
                }
            }
        </script>

        <script>
            function refresh() {
                tableStatus.ajax.url('/status/data/5').load();
            }

            function formatDate(isoString) {
                const date = new Date(isoString);

                let formatted = new Intl.DateTimeFormat('id-ID', {
                    day: 'numeric',
                    month: 'long',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                }).format(date);
                return formatted.replace(" pukul ", " ").replace(/\./g, ":");
            }

            let tableStatus = new DataTable('#statusTable', {
                order: [],
                serverSide: true,
                processing: true,
                // deferLoading: 0,
                ajax: {
                    url: '/status/data/5',
                    type: 'GET',
                },
                responsive: true,
                columns: [{
                        responsivePriority: 1,
                        width: "10%",
                        data: "id",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (row.status == 'success') {
                                    return `
                        <form class="my-2" action="/status/download/5" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="${data}"> 
                            <button class="btn btn-outline-secondary btn-sm ms-auto p-1 m-0" type="submit">
                                <i class="fas fa-download mx-1"></i>
                            </button>
                        </form>
                        `
                                } else {
                                    return '-'
                                }
                            }
                            return data;
                        }
                    },
                    {
                        responsivePriority: 3,
                        width: "10%",
                        data: "type",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (data == 'dashboard-user') {
                                    return 'Report Petugas'
                                } else if (data == 'dashboard-market') {
                                    return 'Report Sentra Ekonomi'
                                } else if (data == 'dashboard-regency') {
                                    return 'Report Kabupaten/Kota'
                                }

                                return '';
                            }
                            return data;
                        }
                    },
                    {
                        responsivePriority: 3,
                        width: "10%",
                        data: "user.firstname",
                        type: "text",
                    },
                    {
                        responsivePriority: 2,
                        width: "10%",
                        data: "status",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {

                                var color = 'info'
                                if (data == 'success') {
                                    color = 'success'
                                } else if (data == 'failed') {
                                    color = 'danger'
                                } else if (data == 'success') {
                                    color = 'success'
                                } else if (data == 'loading' || data == 'processing') {
                                    color = 'secondary'
                                } else if (data == 'success with error') {
                                    color = 'danger'
                                } else {
                                    color = 'info'
                                }

                                return '<p class="mb-0"><span class="badge badge-small bg-' + color +
                                    '">' +
                                    data + '</span></p>';
                            }
                            return data;
                        }
                    },
                    {
                        responsivePriority: 3,
                        width: "10%",
                        data: "created_at",
                        type: "text",
                        render: function(data, type, row) {
                            return formatDate(data)
                        }
                    },
                    {
                        responsivePriority: 4,
                        width: "10%",
                        data: "message",
                        type: "text",
                    },
                ],
                language: {
                    paginate: {
                        previous: '<i class="fas fa-angle-left"></i>',
                        next: '<i class="fas fa-angle-right"></i>'
                    }
                }
            });
        </script>
    @endpush
@endsection
