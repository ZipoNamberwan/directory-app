@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Collapsible chevron rotation */
        .toggle-btn .toggle-icon {
            transition: transform .2s ease;
        }

        .toggle-btn[aria-expanded="true"] .toggle-icon {
            transform: rotate(90deg);
        }

        /* Remove button shadow & outline */
        .toggle-btn,
        .toggle-btn:focus,
        .toggle-btn:active,
        .toggle-btn:hover {
            box-shadow: none !important;
            outline: none !important;
        }

        /* Header spacing */
        #matchingInfoHeader {
            gap: .4rem;
        }

        /* Smaller title styling */
        .matching-info-title {
            font-size: .9rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .matching-info-title i {
            font-size: .85rem;
        }

        /* Chevron button sizing & centering */
        #matchingInfoToggle {
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #matchingInfoToggle .toggle-icon {
            font-size: .85rem;
        }

        /* List item spacing */
        #matchingInfoBody ul li {
            margin-bottom: .4rem;
        }

        #matchingInfoBody ul li:last-child {
            margin-bottom: 0;
        }
    </style>
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
                    <div class="col-md-3">
                        <label class="form-control-label">Status Matching Wilayah</label>
                        <select id="statusMatching" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Status -- </option>
                            <option value="all">Semua</option>
                            <option value="success">Sukses Matching</option>
                            <option value="failed">Gagal Matching</option>
                            <option value="notyet">Belum Matching</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label" for="keyword">Cari</label>
                        <input type="text" name="keyword" class="form-control" id="keyword"
                            placeholder="Cari By Keyword">
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Filter Kabupaten</label>
                        <select style="width: 100%;" id="regency" name="regency" class="form-control"
                            data-toggle="select">
                            <option value="0" disabled selected> -- Filter Kabupaten -- </option>
                            @foreach ($regencies as $regency)
                                <option value="{{ $regency->id }}"
                                    {{ old('regency') == $regency->id ? 'selected' : '' }}>
                                    [{{ $regency->short_code }}] {{ $regency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Kecamatan</label>
                        <select style="width: 100%;" id="subdistrict" name="subdistrict" class="form-control"
                            data-toggle="select">
                            <option value="0" disabled selected> -- Filter Kecamatan -- </option>
                            @foreach ($subdistricts as $subdistrict)
                                <option value="{{ $subdistrict->id }}"
                                    {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                    [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Desa</label>
                        <select id="village" name="village" class="form-control" data-toggle="select"
                            name="village"></select>
                    </div>
                    <div id="sls_div" class="col-md-3">
                        <label class="form-control-label">SLS</label>
                        <select id="sls" name="sls" class="form-control" data-toggle="select"></select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label d-block">Mode Tampilan Tabel:</label>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mode" id="fitColumns"
                                value="fit" checked>
                            <label class="form-check-label" for="fit">Muat Semua Kolom</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mode" id="fitDataTable"
                                value="responsive">
                            <label class="form-check-label" for="responsive">Responsif</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mode" id="fitDataTable"
                                value="scroll">
                            <label class="form-check-label" for="scroll">Scroll Horizontal</label>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <p class="mb-2 text-muted small">
                            Jumlah usaha yang difilter: <span id="total-records" class="fw-bold">0</span>
                        </p>
                    </div>
                </div>
                <div id="data-table"></div>
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
    <script src="/vendor/tabulator/tabulator.min.js"></script>
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
            {
                selector: '#statusMatching',
                placeholder: 'Pilih Status Matching'
            },
            {
                selector: '#regency',
                placeholder: 'Pilih Kabupaten'
            },
            {
                selector: '#subdistrict',
                placeholder: 'Pilih Kecamatan'
            },
            {
                selector: '#village',
                placeholder: 'Pilih Desa'
            },
            {
                selector: '#sls',
                placeholder: 'Pilih SLS'
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
            '#statusMatching': () => {
                renderTable()
            },
            '#regency': () => {
                loadSubdistrict(null, null);
                renderTable()
            },
            '#subdistrict': () => {
                loadVillage(null, null);
                renderTable()
            },
            '#village': () => {
                loadSls(null, null);
                renderTable()
            },
            '#sls': () => {
                renderTable()
            },
        };

        function loadSubdistrict(regencyid = null, selectedvillage = null) {

            let regencySelector = `#regency`;
            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(regencySelector).val();
            if (regencyid != null) {
                id = regencyid;
            }

            $(subdistrictSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/kec/' + id,
                    success: function(response) {
                        $(subdistrictSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Kecamatan -- </option>`);
                        $(villageSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);

                        response.forEach(element => {
                            let selected = selectedvillage == String(element.id) ? 'selected' : '';
                            $(subdistrictSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(subdistrictSelector).empty().append(`<option value="0" disabled> -- Pilih Kecamatan -- </option>`);
                $(villageSelector).empty().append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

        function loadVillage(subdistrictid = null, selectedvillage = null) {

            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(subdistrictSelector).val();
            if (subdistrictid != null) {
                id = subdistrictid;
            }

            $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/desa/' + id,
                    success: function(response) {
                        $(villageSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                        response.forEach(element => {
                            let selected = selectedvillage == String(element.id) ? 'selected' : '';
                            $(villageSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(villageSelector).empty().append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

        function loadSls(villageid = null, selectedsls = null) {

            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(villageSelector).val();
            if (villageid != null) {
                id = villageid;
            }

            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/sls/' + id,
                    success: function(response) {
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                        response.forEach(element => {
                            let selected = selectedsls == String(element.id) ? 'selected' : '';
                            $(slsSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

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
                if (filter == 'keyword') {
                    filterUrl = `&${filter}=` + e.value
                } else {
                    var filterselected = e.options[e.selectedIndex];
                    if (filterselected != null) {
                        var filterid = filterselected.value
                        if (filterid != 0) {
                            filterUrl = `&${filter}=` + filterid
                        }
                    }
                }
            }
            return filterUrl
        }

        function renderTable() {
            filterUrl = ''
            filterTypes = ['organization', 'market', 'user', 'marketType', 'statusMatching',
                'regency', 'subdistrict',
                'village', 'sls', 'keyword'
            ]
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            table.setData('/pasar/data?' + filterUrl);
        }

        function toTitleCase(input) {
            const str = String(input); // ensure it's a string
            return str
                .toLowerCase()
                .split(/\s+/)
                .filter(Boolean)
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(" ");
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

        function truncateText(text, maxLength) {
            if (text.length <= maxLength) {
                return text;
            }
            return text.substring(0, maxLength) + "...";
        }

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

    <script>
        // Global table variable
        let table;

        // Define column configurations for different modes
        const getColumnConfig = (mode) => {
            const baseColumns = [{
                    title: "Name",
                    field: "name",
                    responsive: 0,
                    formatter: function(cell) {
                        return `<div class="text-wrap font-weight-bold">${$("<div>").text(cell.getValue()).html()}</div>`;
                    }
                },
                {
                    title: "Sentra Ekonomi",
                    field: "market",
                    responsive: 2,
                    formatter: function(cell) {
                        let data = cell.getValue();
                        if (!data) return "-";
                        return `<div class="text-wrap lh-sm"><span class="small mb-0">${data.name}</span></div>`;
                    }
                },
                {
                    title: "Detail",
                    field: "detail",
                    responsive: 1,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        let html = `<div class="my-1 small text-wrap lh-sm">`;

                        if (row.owner) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Pemilik:</span> <span class="fw-semibold text-dark">${row.owner}</span></div>`;
                        }
                        if (row.status) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Status:</span> <span class="fw-semibold text-dark">${row.status}</span></div>`;
                        }
                        if (row.address) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Alamat:</span> <span class="fw-semibold text-dark">${row.address}</span></div>`;
                        }
                        if (row.description) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Deskripsi:</span> <span class="fw-semibold text-dark">${truncateText(row.description, 60)}</span></div>`;
                        }
                        if (row.sector) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Sektor:</span> <span class="fw-semibold text-dark">${truncateText(row.sector, 40)}</span></div>`;
                        }
                        if (row.notes) {
                            html +=
                                `<div><span class="text-muted">Catatan:</span> <span class="fw-semibold text-dark">${row.notes}</span></div>`;
                        }

                        html += `</div>`;
                        return html;
                    }
                },
                {
                    title: "Lokasi",
                    field: "location",
                    responsive: 4,
                    hozAlign: "center",
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        if (row.latitude && row.longitude) {
                            const lat = row.latitude;
                            const lng = row.longitude;
                            const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
                            return `
                        <a href="${mapsUrl}" target="_blank" class="d-inline-flex align-items-center justify-content-center 
                            rounded-circle bg-light text-primary" 
                            style="width:32px; height:32px;" title="Lihat Lokasi">
                            <i class="fas fa-map-marker-alt fa-lg"></i>
                        </a>`;
                        }
                        return "-";
                    }
                },
                {
                    title: "Wilayah",
                    field: "sls",
                    responsive: 2,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();

                        if (row.match_level === 'failed') {
                            return `<div class="text-wrap lh-sm">
                        <div class="text-warning fw-semibold small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Tidak ada polygon yang pas untuk titik ini, bisa cek koordinatnya dulu
                        </div>
                    </div>`;
                        }

                        let areaId = "";
                        if (row.sls && row.sls.id) {
                            areaId = row.sls.id;
                        } else if (row.village && row.village.id) {
                            areaId = row.village.id;
                        } else if (row.subdistrict && row.subdistrict.id) {
                            areaId = row.subdistrict.id;
                        } else if (row.regency && row.regency.id) {
                            areaId = row.regency.id;
                        }

                        let areaNames = [];
                        if (row.regency && row.regency.name) {
                            areaNames.push(row.regency.name);
                        }
                        if (row.subdistrict && row.subdistrict.name) {
                            areaNames.push(row.subdistrict.name);
                        }
                        if (row.village && row.village.name) {
                            areaNames.push(row.village.name);
                        }
                        if (row.sls && row.sls.name) {
                            areaNames.push(row.sls.name);
                        }

                        let areaName = areaNames.length > 0 ? areaNames.join(", ") : "-";

                        if (!areaId && !areaName) return "-";

                        return `<div class="text-wrap lh-sm">
                    ${areaId ? `<div class="fw-semibold text-success">${toTitleCase(areaId)}</div>` : ""}
                    ${areaName !== "-" ? `<div class="small text-muted">${toTitleCase(areaName)}</div>` : ""}
                </div>`;
                    }
                },
                {
                    title: "Satker",
                    field: "market.organization",
                    responsive: 2,
                    formatter: function(cell) {
                        let data = cell.getValue();
                        if (!data) return "-";
                        return `<div class="text-wrap lh-sm"><span class="small text-muted mb-0">[${data.long_code}] ${data.name}</span></div>`;
                    }
                },
                {
                    title: "Petugas",
                    field: "user",
                    responsive: 3,
                    formatter: function(cell) {
                        const value = cell.getValue()?.firstname ?? "-";
                        return `<div class="small text-wrap lh-sm">${value}</div>`;
                    }
                },
                {
                    title: "Dibuat Pada",
                    field: "created_at",
                    responsive: 6,
                    formatter: function(cell) {
                        return `<div class="small text-wrap lh-sm">${formatDate(cell.getValue())}</div>`;
                    }
                },
                {
                    title: "Aksi",
                    field: "id",
                    responsive: 7,
                    hozAlign: "center",
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        if (canDelete(row.user.id) && row.project?.type !== "kendedes mobile") {
                            return `
                        <form id="formdelete${row.id}" name="formdelete${row.id}" 
                            onSubmit="deleteBusiness('${row.id}','${row.name}')" 
                            class="d-inline" action="/pasar/${row.id}" method="POST">
                            @csrf
                            @method('delete')
                            <button class="btn btn-outline-danger btn-sm p-1" type="submit">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>`;
                        }
                        return "-";
                    }
                }
            ];

            // Apply mode-specific configurations
            if (mode === "fit") {
                // No width/minWidth, no responsive collapse column
                return baseColumns;
            } else if (mode === "responsive") {
                // Add responsive collapse column at the beginning
                const responsiveColumn = {
                    formatter: "responsiveCollapse",
                    width: 30,
                    hozAlign: "center",
                    resizable: false,
                    headerSort: false
                };

                // Set widths for responsive mode
                baseColumns[0].widthGrow = 3;
                baseColumns[0].minWidth = 150;
                baseColumns[1].width = 250;
                baseColumns[1].minWidth = 200;
                baseColumns[2].width = 100;
                baseColumns[2].minWidth = 80;
                baseColumns[3].width = 200;
                baseColumns[3].minWidth = 150;
                baseColumns[4].width = 200;
                baseColumns[4].minWidth = 150;
                baseColumns[5].width = 150;
                baseColumns[5].minWidth = 120;
                baseColumns[6].width = 150;
                baseColumns[6].minWidth = 120;
                baseColumns[7].width = 150;
                baseColumns[7].minWidth = 120;
                baseColumns[8].width = 70;
                baseColumns[8].minWidth = 60;

                return [responsiveColumn, ...baseColumns];
            } else { // scroll horizontal
                // Set widths for horizontal scroll mode
                baseColumns[0].widthGrow = 3;
                baseColumns[0].minWidth = 150;
                baseColumns[1].width = 250;
                baseColumns[1].minWidth = 200;
                baseColumns[2].width = 100;
                baseColumns[2].minWidth = 80;
                baseColumns[3].width = 200;
                baseColumns[3].minWidth = 150;
                baseColumns[4].width = 200;
                baseColumns[4].minWidth = 150;
                baseColumns[5].width = 150;
                baseColumns[5].minWidth = 120;
                baseColumns[6].width = 150;
                baseColumns[6].minWidth = 120;
                baseColumns[7].width = 150;
                baseColumns[7].minWidth = 120;
                baseColumns[8].width = 70;
                baseColumns[8].minWidth = 60;

                return baseColumns;
            }
        };

        // Get table configuration based on mode
        const getTableConfig = (mode) => {
            const baseConfig = {
                height: "800px",
                layout: "fitColumns",
                ajaxURL: "/pasar/data",
                progressiveLoad: "scroll",
                paginationSize: 20,
                placeholder: "Tidak ada usaha yang ditemukan",
                textDirection: "auto",
                ajaxResponse: function(url, params, response) {
                    document.getElementById("total-records").textContent = response.total_records;
                    return response;
                },
                columns: getColumnConfig(mode)
            };

            if (mode === "responsive") {
                baseConfig.responsiveLayout = "collapse";
                baseConfig.responsiveLayoutCollapseStartOpen = false;
            }

            return baseConfig;
        };

        // Initialize table with default mode
        const initializeTable = (mode = "fit") => {
            table = new Tabulator("#data-table", getTableConfig(mode));
        };

        // Recreate table with new mode
        const recreateTable = (mode) => {
            if (table) {
                table.destroy();
            }
            initializeTable(mode);
        };

        // Reset all Select2 filters without triggering change events
        const resetSelect2Filters = () => {
            selectConfigs.forEach(({
                selector
            }) => {
                // Reset value without triggering change event
                $(selector).val(null);
                // Update the Select2 display without triggering change
                $(selector).trigger('change.select2');
            });

            document.getElementById('keyword').value = '';
        };

        // Event listener for mode changes
        document.querySelectorAll('input[name="mode"]').forEach(radio => {
            radio.addEventListener("change", function(e) {
                let mode = e.target.value;
                console.log("Mode changed to:", mode);

                // Reset all Select2 filters
                resetSelect2Filters();

                // Recreate table with new mode configuration
                recreateTable(mode);
            });
        });

        // Initialize table on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Get initial mode from checked radio button
            const checkedRadio = document.querySelector('input[name="mode"]:checked');
            const initialMode = checkedRadio ? checkedRadio.value : "fit";

            initializeTable(initialMode);
        });
    </script>
@endpush
