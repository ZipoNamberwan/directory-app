@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Daftar Direktori Usaha Sentra Ekonomi'])
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

        <div class="card mt-2">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="text-capitalize">Daftar Usaha yang Telah Diupload</h6>
                    <div class="d-flex">
                        <a href="/pasar/muatan" class="btn btn-info btn-lg ms-auto p-2 me-2" role="button">
                            <span class="btn-inner--icon"><i class="fas fa-map"></i></span>
                            <span class="ml-3 btn-inner--text">Peta</span>
                        </a>
                        <form action="/pasar/download" class="me-2" method="POST">
                            @csrf
                            <input type="hidden" name="organization" id="organization_download">
                            <input type="hidden" name="market" id="market_download">
                            <button type="submit" class="btn btn-primary mb-0 p-2">Download</button>
                        </form>
                        <button onclick="refresh()" class="btn btn-outline-primary mb-0 p-2" data-bs-toggle="modal"
                            data-bs-target="#statusDialog">
                            <i class="fas fa-circle-info me-2"></i>
                            Status
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    @hasrole('adminprov')
                        <div class="col-md-3">
                            <label class="form-control-label">Satker <span class="text-danger">*</span></label>
                            <select id="organization" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Satker -- </option>
                                @foreach ($organizations as $organization)
                                    <option value="{{ $organization->id }}"
                                        {{ old('organization') == $organization->id ? 'selected' : '' }}>
                                        [{{ $organization->short_code }}] {{ $organization->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endhasrole
                    <div class="col-md-3">
                        <label class="form-control-label">Tipe <span class="text-danger">*</span></label>
                        <select id="marketType" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Tipe -- </option>
                            @foreach ($marketTypes as $marketType)
                                <option value="{{ $marketType->id }}"
                                    {{ old('marketType') == $marketType->id ? 'selected' : '' }}>
                                    {{ $marketType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Sentra Ekonomi <span class="text-danger">*</span></label>
                        <select id="market" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Sentra Ekonomi -- </option>
                            @foreach ($markets as $market)
                                <option value="{{ $market->id }}" {{ old('market') == $market->id ? 'selected' : '' }}>
                                    {{ $market->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    @hasrole('adminkab|pml|operator')
                        <div class="col-md-3">
                            <label class="form-control-label">Petugas <span class="text-danger">*</span></label>
                            <select id="user" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Petugas -- </option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user') == $user->id ? 'selected' : '' }}>
                                        {{ $user->firstname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endhasrole
                </div>
                <table id="myTable" class="align-items-center mb-0 text-sm">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Usaha</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Status Bangunan</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Alamat Lengkap</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Deskripsi</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Sektor</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Catatan</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Sentra Ekonomi</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Kabupaten</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">User yang Upload</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Created At</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusDialog" tabindex="-1" role="dialog" aria-labelledby="statusDialogLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Status Download</h5>
                    <button onclick="refresh()" class="btn btn-sm btn-outline-primary mb-0 p-2">
                        Refresh
                    </button>
                    {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
                </div>
                <div class="modal-body">
                    <table id="statusTable" class="align-items-center mb-0 text-sm">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">File</th>
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
    <script src="/vendor/sweetalert2/sweetalert2.js"></script>

    <script>
        const selectConfigs = [{
                selector: '#organization',
                placeholder: 'Pilih Satker'
            },
            {
                selector: '#market',
                placeholder: 'Pilih Sentra Ekonomi'
            },
            {
                selector: '#user',
                placeholder: 'Pilih Petugas'
            },
            {
                selector: '#marketType',
                placeholder: 'Pilih Tipe'
            },
        ];

        selectConfigs.forEach(({
            selector,
            placeholder
        }) => {
            $(selector).select2({
                placeholder,
                allowClear: true
            });
        });

        const eventHandlers = {
            '#organization': () => {
                loadMarketType()
                loadMarket(null, null)
                renderTable()
                updateDownloadHidden()
            },
            '#market': () => {
                renderTable()
                updateDownloadHidden()
            },
            '#user': () => {
                renderTable()
            },
            '#marketType': () => {
                loadMarket(null, null)
                renderTable()
            },
        };

        Object.entries(eventHandlers).forEach(([selector, handler]) => {
            $(selector).on('change', handler);
        });

        function updateDownloadHidden() {
            document.getElementById('organization_download').value = $('#organization').val();
            document.getElementById('market_download').value = $('#market').val();
        }
        updateDownloadHidden();

        function loadMarket(organizationid = null, selectedmarket = null) {
            const organizationSelector = '#organization';
            const marketTypeSelector = '#marketType';
            const marketSelector = '#market';

            // Check if organization input is available (adminprov)
            const organizationExists = $(organizationSelector).length > 0;

            let organizationId = organizationExists ? $(organizationSelector).val() : null;
            if (organizationid != null) {
                organizationId = organizationid;
            }

            const marketType = $(marketTypeSelector).val();

            $(marketSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            let url = `/pasar/filter`;
            let query = [];

            if (organizationExists && organizationId) {
                query.push(`organization=${organizationId}`);
            }

            if (marketType && marketType !== 'all') {
                query.push(`marketType=${marketType}`);
            }

            if (query.length > 0) {
                url += '?' + query.join('&');
            }

            $.ajax({
                type: 'GET',
                url: url,
                success: function(response) {
                    $(marketSelector).empty().append(
                        `<option value="0" disabled selected> -- Pilih Sentra Ekonomi -- </option>`
                    );
                    response.forEach(element => {
                        const selected = selectedmarket == String(element.id) ? 'selected' : '';
                        $(marketSelector).append(
                            `<option value="${element.id}" ${selected}>${element.name}</option>`
                        );
                    });
                },
                error: function() {
                    $(marketSelector).empty().append(
                        `<option value="0" disabled> -- Gagal Memuat Data -- </option>`);
                }
            });
        }

        function loadMarketType() {

            let organizationSelector = `#organization`;
            let marketTypeSelector = `#marketType`;

            let id = $(organizationSelector).val();

            $(marketTypeSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/pasar/type/',
                    success: function(response) {
                        $(marketTypeSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Tipe -- </option>`);
                        response.forEach(element => {
                            $(marketTypeSelector).append(
                                `<option value="${element.id}">${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(marketTypeSelector).empty().append(`<option value="0" disabled> -- Pilih Tipe -- </option>`);
            }
        }

        function getFilterUrl(filter) {
            var filterUrl = ''
            var e = document.getElementById(filter);
            if (e != null) {
                var filterselected = e.options[e.selectedIndex];
                if (filterselected != null) {
                    var filterid = filterselected.value
                    if (filterid != 0) {
                        filterUrl = `&${filter}=` + filterid
                    }
                }
            }

            return filterUrl
        }

        function renderTable() {
            filterUrl = ''
            filterTypes = ['organization', 'market', 'user', 'marketType']
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            table.ajax.url('/pasar/data?' + filterUrl).load();
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

        var isAdmin = @json($isAdmin);
        var userId = @json($userId);

        function canDelete($id) {
            return isAdmin || $id == userId;
        }

        function deleteBusiness(id, name) {
            event.preventDefault();
            Swal.fire({
                title: `Hapus Usaha Ini?`,
                text: name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya',
                cancelButtonText: 'Tidak',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('formdelete' + id).submit();
                }
            })
        }

        let table = new DataTable('#myTable', {
            order: [],
            serverSide: true,
            processing: true,
            // deferLoading: 0,
            ajax: {
                url: '/pasar/data',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "name",
                    type: "text",
                    render: function(data, type, row) {
                        return $('<div>').text(data).html();
                    }
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "status",
                    type: "text",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "address",
                    type: "text",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "description",
                    type: "text",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "sector",
                    type: "text",
                },
                {
                    responsivePriority: 3,
                    width: "10%",
                    data: "note",
                    type: "text",
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "market",
                    type: "text",
                    render: function(data, type, row) {
                        return data.name
                    }
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "regency",
                    type: "text",
                    render: function(data, type, row) {
                        var doneBy = '';
                        if (row.market.organization.id == '3500') {
                            doneBy = 'Dikerjakan oleh BPS Provinsi'
                        }
                        if (type === 'display') {
                            return `<div class="my-1"> 
                                    <p style='font-size: 0.875rem' class='text-secondary mb-0'>[${data.long_code}] ${data.name}</p>
                                    <p style='font-size: 0.875rem' class='text-secondary mb-0 text-bold'>${doneBy}</p>
                                </div>`
                        }
                        return data
                    }
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "user",
                    type: "text",
                    render: function(data, type, row) {
                        return data.firstname;
                    }
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "created_at",
                    type: "text",
                    render: function(data, type, row) {
                        return formatDate(data)
                    }
                },
                {
                    responsivePriority: 3,
                    width: "10%",
                    data: "id",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            if (canDelete(row.user.id)) {
                                return `
                                <form id="formdelete${data}" name="formdelete${data}" onSubmit="deleteBusiness('${data}','${row.name}')" 
                                    class="my-2" action="/pasar/${data}" method="POST">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-outline-danger btn-sm ms-auto p-1 m-0" type="submit">
                                        <i class="fas fa-trash mx-1"></i>
                                    </button>
                                </form>
                        `;
                            } else {
                                return '-'
                            }
                        }
                        return data;
                    }
                },
            ],
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            }
        });

        function refresh() {
            tableStatus.ajax.url('/status/data/3').load();
        }

        let tableStatus = new DataTable('#statusTable', {
            order: [],
            serverSide: true,
            processing: true,
            // deferLoading: 0,
            ajax: {
                url: '/status/data/3',
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
                        <form class="my-2" action="/status/download/3" method="POST">
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
