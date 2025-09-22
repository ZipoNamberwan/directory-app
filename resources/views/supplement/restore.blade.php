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

        /* Fix z-index issue - Modal should be above sidenav */
        .modal {
            z-index: 1055 !important;
        }

        .modal-backdrop {
            z-index: 1050 !important;
        }

        /* Ensure sidenav stays behind modal */
        .sidenav {
            z-index: 1030 !important;
        }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Daftar Direktori Usaha Suplemen'])
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
                    <h5 class="text-capitalize">Daftar Usaha Suplemen</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    @hasrole('adminprov')
                        <div class="col-md-3">
                            <label class="form-control-label">Satker</label>
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
                    @hasrole('adminkab')
                        <div class="col-md-3">
                            <label class="form-control-label">Petugas</label>
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
                        <label class="form-control-label">Tipe Projek</label>
                        <select id="projectType" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Tipe Projek -- </option>
                            @foreach ($projectTypes as $projectType)
                                <option value="{{ $projectType['value'] }}">
                                    {{ $projectType['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
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
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-control-label">Kabupaten</label>
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
                    <div class="col-md-8">
                        <p class="mb-2 text-muted small">
                            Jumlah usaha yang difilter: <span id="total-records" class="fw-bold">0</span>
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button type="button" id="restore-selected-btn" class="btn btn-success btn-sm" disabled>
                            <i class="fas fa-trash-can-arrow-up me-2"></i>
                            Restore Usaha Terpilih (<span id="selected-count">0</span>)
                        </button>
                    </div>
                </div>
                <div id="data-table"></div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="/vendor/select2/select2.min.js"></script>
    <script src="/vendor/sweetalert2/sweetalert2.js"></script>

    <script src="/vendor/tabulator/tabulator.min.js"></script>
    <script src="/vendor/datatables/dataTables.min.js"></script>
    <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

    <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

    <script>
        const selectConfigs = [{
                selector: '#organization',
                placeholder: 'Pilih Satker'
            },
            {
                selector: '#market',
                placeholder: 'Pilih Pasar'
            },
            {
                selector: '#user',
                placeholder: 'Pilih Petugas'
            },
            {
                selector: '#projectType',
                placeholder: 'Pilih Tipe Projek'
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
                renderTable()
            },
            '#market': () => {
                renderTable()
            },
            '#user': () => {
                renderTable()
            },
            '#projectType': () => {
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
            filterTypes = ['organization', 'market', 'user',
                'projectType', 'statusMatching',
                'regency', 'subdistrict',
                'village', 'sls', 'keyword'
            ];
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            table.setData('/suplemen/data?is_deleted_only=1' + filterUrl);
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

        function truncateText(text, maxLength) {
            if (text.length <= maxLength) {
                return text;
            }
            return text.substring(0, maxLength) + "...";
        }

        function refresh() {
            tableStatus.ajax.url('/status/data/2').load();
        }

        let tableStatus = new DataTable('#statusTable', {
            order: [],
            serverSide: true,
            processing: true,
            // deferLoading: 0,
            ajax: {
                url: '/status/data/2',
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
                            if (row.status === 'success') {
                                if (row.file_has_deleted == 0) {
                                    return `
                                        <form class="my-2" action="/status/download/2" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="${data}"> 
                                            <button class="btn btn-outline-secondary btn-sm ms-auto p-1 m-0" type="submit">
                                                <i class="fas fa-download mx-1"></i>
                                            </button>
                                        </form>
                                    `;
                                } else {
                                    return `<span class="badge bg-danger">Dihapus</span>`;
                                }
                            } else {
                                return '-';
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
        // debounce function
        function debounce(func, delay) {
            let timer;
            return function(...args) {
                clearTimeout(timer); // clear previous timer
                timer = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        }

        // your action when typing finished
        function handleSearch(e) {
            const keyword = e.target.value.trim();
            renderTable();
        }

        // attach to input with debounce
        const input = document.getElementById("keyword");
        input.addEventListener("input", debounce(handleSearch, 500));
    </script>

    <script>
        // Global table variable
        let table;
        let organizationId = @json($organizationId);
        let canDeletePermission = @json($canDelete);
        let isAdminProv = @json(auth()->user()->hasRole('adminprov'));

        const canDelete = (permission, businessOrganizationId) =>
            permission && (isAdminProv || organizationId === businessOrganizationId);

        // Define column configurations for different modes
        const getColumnConfig = (mode) => {
            const baseColumns = [{
                    title: `<div class="form-check">
                        <input class="form-check-input" type="checkbox" id="select-all-businesses">
                    </div>`,
                    field: "checkbox",
                    responsive: 0,
                    width: 50,
                    headerSort: false,
                    hozAlign: "center",
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        const businessId = row.id;

                        // Check if this business should be selected based on current state
                        const isChecked = selectAllState || selectedBusinessIds.has(businessId);

                        return `<div class="form-check">
                            <input class="form-check-input business-checkbox" type="checkbox" value="${businessId}" ${isChecked ? 'checked' : ''}>
                        </div>`;
                    }
                },
                {
                    title: "Nama",
                    field: "name",
                    responsive: 0,
                    formatter: function(cell) {
                        return `<div class="text-wrap font-weight-bold">${$("<div>").text(cell.getValue()).html()}</div>`;
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
                        if (row.note) {
                            html +=
                                `<div><span class="text-muted">Catatan:</span> <span class="fw-semibold text-dark">${row.note}</span></div>`;
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
                    field: "organization",
                    responsive: 2,
                    formatter: function(cell) {
                        let data = cell.getValue();
                        if (!data) return "-";
                        return `<div class="text-wrap lh-sm"><span class="small mb-0">[${data.long_code}] ${data.name}</span></div>`;
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
                    title: "Projek",
                    field: "project",
                    responsive: 5,
                    formatter: function(cell) {
                        let data = cell.getValue();
                        if (!data) return "-";
                        const projectName = (data.type === "kendedes mobile") ? "Kendedes Mobile" : "SWMAPS";
                        return `<div class="small text-wrap lh-sm">${projectName}</div>`;
                    }
                },
                {
                    title: "Dibuat Pada",
                    field: "created_at",
                    responsive: 6,
                    formatter: function(cell) {
                        return `<div class="small text-wrap lh-sm">${formatDate(cell.getValue())}</div>`;
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
                baseColumns[0].width = 50;
                baseColumns[0].minWidth = 50;
                baseColumns[1].widthGrow = 3;
                baseColumns[1].minWidth = 150;
                baseColumns[2].width = 250;
                baseColumns[2].minWidth = 200;
                baseColumns[3].width = 100;
                baseColumns[3].minWidth = 80;
                baseColumns[4].width = 200;
                baseColumns[4].minWidth = 150;
                baseColumns[5].width = 200;
                baseColumns[5].minWidth = 150;
                baseColumns[6].width = 150;
                baseColumns[6].minWidth = 120;
                baseColumns[7].width = 150;
                baseColumns[7].minWidth = 120;
                baseColumns[8].width = 150;
                baseColumns[8].minWidth = 120;

                return [responsiveColumn, ...baseColumns];
            } else { // scroll horizontal
                // Set widths for horizontal scroll mode
                baseColumns[0].width = 50;
                baseColumns[0].minWidth = 50;
                baseColumns[1].widthGrow = 3;
                baseColumns[1].minWidth = 150;
                baseColumns[2].width = 250;
                baseColumns[2].minWidth = 200;
                baseColumns[3].width = 100;
                baseColumns[3].minWidth = 80;
                baseColumns[4].width = 200;
                baseColumns[4].minWidth = 150;
                baseColumns[5].width = 200;
                baseColumns[5].minWidth = 150;
                baseColumns[6].width = 150;
                baseColumns[6].minWidth = 120;
                baseColumns[7].width = 150;
                baseColumns[7].minWidth = 120;
                baseColumns[8].width = 150;
                baseColumns[8].minWidth = 120;

                return baseColumns;
            }
        };

        // Get table configuration based on mode
        const getTableConfig = (mode) => {
            const baseConfig = {
                height: "800px",
                layout: "fitColumns",
                ajaxURL: "/suplemen/data?is_deleted_only=1",
                progressiveLoad: "scroll",
                paginationSize: 20,
                placeholder: "Tidak ada usaha yang ditemukan",
                textDirection: "auto",
                ajaxResponse: function(url, params, response) {
                    // Update total records count
                    totalRecords = response.total_records || 0;
                    document.getElementById("total-records").textContent = totalRecords;

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

            // Reset selection state
            selectAllState = false;
            selectedBusinessIds.clear();
            totalRecords = 0;
            document.getElementById('select-all-businesses').checked = false;
            updateRestoreButton();
        };

        // Event listener for mode changes
        document.querySelectorAll('input[name="mode"]').forEach(radio => {
            radio.addEventListener("change", function(e) {
                let mode = e.target.value;

                // Reset all Select2 filters
                resetSelect2Filters();

                // Recreate table with new mode configuration
                recreateTable(mode);
            });
        });

        // Initialize table on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 PAGE LOADED - Initial state:', {
                selectAllState,
                selectedBusinessIds: Array.from(selectedBusinessIds),
                totalRecords
            });
            
            // Get initial mode from checked radio button
            const checkedRadio = document.querySelector('input[name="mode"]:checked');
            const initialMode = checkedRadio ? checkedRadio.value : "fit";

            console.log('🚀 Initializing table with mode:', initialMode);
            initializeTable(initialMode);
        });

        // Replace the existing checkbox and restore functionality section with this improved version

        // Checkbox and restore functionality
        let selectAllState = false; // Track the select all state
        let selectedBusinessIds = new Set(); // Track manually selected business IDs
        let totalRecords = 0; // Track total number of records

        const updateRestoreButton = () => {
            const restoreBtn = document.getElementById('restore-selected-btn');
            const selectedCount = document.getElementById('selected-count');

            const count = selectedBusinessIds.size;
            console.log('🔄 updateRestoreButton:', {
                count,
                selectedBusinessIds: Array.from(selectedBusinessIds),
                selectAllState,
                restoreBtnDisabled: count === 0
            });
            
            selectedCount.textContent = count;
            restoreBtn.disabled = count === 0;
        };

        const updateSelectAllCheckboxState = () => {
            const selectAllCheckbox = document.getElementById('select-all-businesses');
            const businessCheckboxes = document.querySelectorAll('.business-checkbox');
            const checkedBoxes = document.querySelectorAll('.business-checkbox:checked');

            console.log('🔄 updateSelectAllCheckboxState:', {
                totalCheckboxes: businessCheckboxes.length,
                checkedCheckboxes: checkedBoxes.length,
                selectAllState,
                selectedBusinessIdsSize: selectedBusinessIds.size
            });

            if (businessCheckboxes.length === 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
                console.log('📝 No checkboxes found - select all unchecked');
                return;
            }

            if (selectAllState) {
                // If select all is active, checkbox should be checked
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
                console.log('📝 Select all state active - checkbox checked');
            } else if (checkedBoxes.length === businessCheckboxes.length) {
                // All visible boxes are checked individually
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
                console.log('📝 All visible boxes checked individually');
            } else if (checkedBoxes.length > 0) {
                // Some but not all are checked
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
                console.log('📝 Some boxes checked - indeterminate state');
            } else {
                // None are checked
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
                console.log('📝 No boxes checked - select all unchecked');
            }
        };

        // Handle "Select All" checkbox
        const handleSelectAll = (e) => {
            const isChecked = e.target.checked;
            const businessCheckboxes = document.querySelectorAll('.business-checkbox');
            
            console.log('🟦 SELECT ALL CLICKED:', {
                isChecked,
                previousSelectAllState: selectAllState,
                visibleCheckboxes: businessCheckboxes.length,
                selectedBusinessIdsBeforeAction: Array.from(selectedBusinessIds),
                selectedBusinessIdsSizeBefore: selectedBusinessIds.size
            });
            
            selectAllState = isChecked;

            if (isChecked) {
                // When select all is checked, check all visible checkboxes and add their IDs
                let checkedCount = 0;
                businessCheckboxes.forEach(checkbox => {
                    if (!checkbox.checked) {
                        checkbox.checked = true;
                        checkedCount++;
                    }
                    const businessId = checkbox.value;
                    selectedBusinessIds.add(businessId);
                    console.log(`  ✅ Added business ID: ${businessId}`);
                });
                console.log(`🟦 SELECT ALL: Checked ${checkedCount} new checkboxes, total selected: ${selectedBusinessIds.size}`);
            } else {
                // When unchecked, only uncheck visible checkboxes and remove them from selectedBusinessIds
                let uncheckedCount = 0;
                businessCheckboxes.forEach(checkbox => {
                    if (checkbox.checked) {
                        checkbox.checked = false;
                        uncheckedCount++;
                    }
                    const businessId = checkbox.value;
                    selectedBusinessIds.delete(businessId);
                    console.log(`  ❌ Removed business ID: ${businessId}`);
                });
                console.log(`🟦 SELECT ALL: Unchecked ${uncheckedCount} checkboxes, remaining selected: ${selectedBusinessIds.size}`);
            }

            console.log('🟦 SELECT ALL RESULT:', {
                newSelectAllState: selectAllState,
                selectedBusinessIdsAfterAction: Array.from(selectedBusinessIds),
                selectedBusinessIdsSizeAfter: selectedBusinessIds.size
            });

            updateRestoreButton();
        };

        // Handle individual business checkbox
        const handleBusinessCheckbox = (e) => {
            const checkbox = e.target;
            const businessId = checkbox.value;

            console.log('🟨 INDIVIDUAL CHECKBOX CLICKED:', {
                businessId,
                isChecked: checkbox.checked,
                previousSelectAllState: selectAllState,
                selectedBusinessIdsBeforeAction: Array.from(selectedBusinessIds),
                selectedBusinessIdsSizeBefore: selectedBusinessIds.size
            });

            if (checkbox.checked) {
                selectedBusinessIds.add(businessId);
                console.log(`  ✅ Added business ID ${businessId} to selection`);
            } else {
                selectedBusinessIds.delete(businessId);
                console.log(`  ❌ Removed business ID ${businessId} from selection`);
                // If any item is unchecked, "select all" should be false
                selectAllState = false;
                console.log('  🟨 Set selectAllState to false due to unchecking');
            }

            console.log('🟨 INDIVIDUAL CHECKBOX RESULT:', {
                businessId,
                newSelectAllState: selectAllState,
                selectedBusinessIdsAfterAction: Array.from(selectedBusinessIds),
                selectedBusinessIdsSizeAfter: selectedBusinessIds.size
            });

            updateSelectAllCheckboxState();
            updateRestoreButton();
        };

        // Function to sync checkboxes when new rows are loaded
        const syncCheckboxesWithState = () => {
            const businessCheckboxes = document.querySelectorAll('.business-checkbox');

            console.log('🔄 SYNC CHECKBOXES WITH STATE:', {
                visibleCheckboxes: businessCheckboxes.length,
                selectAllState,
                selectedBusinessIdsSize: selectedBusinessIds.size,
                selectedBusinessIds: Array.from(selectedBusinessIds)
            });

            let syncedCount = 0;
            businessCheckboxes.forEach(checkbox => {
                const businessId = checkbox.value;
                const wasChecked = checkbox.checked;

                // Only check boxes that were individually selected (not due to selectAllState)
                // Don't automatically check new rows when selectAllState is true
                if (selectedBusinessIds.has(businessId)) {
                    checkbox.checked = true;
                    if (!wasChecked) {
                        syncedCount++;
                        console.log(`  ✅ Synced checkbox for business ID: ${businessId}`);
                    }
                } else {
                    checkbox.checked = false;
                    if (wasChecked) {
                        console.log(`  ❌ Unchecked business ID: ${businessId}`);
                    }
                }
            });

            console.log(`🔄 SYNC RESULT: ${syncedCount} checkboxes synced`);

            updateSelectAllCheckboxState();
            updateRestoreButton();
        };

        // Restore selected businesses
        const restoreSelectedBusinesses = () => {
            const allSelectedIds = Array.from(selectedBusinessIds);

            if (allSelectedIds.length === 0) {
                Swal.fire({
                    title: 'Peringatan',
                    text: 'Pilih minimal satu usaha untuk direstore.',
                    icon: 'warning'
                });
                return;
            }

            Swal.fire({
                title: 'Konfirmasi Restore',
                text: `Apakah Anda yakin ingin merestore ${allSelectedIds.length} usaha yang dipilih?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Restore!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Sedang merestore usaha yang dipilih',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Make AJAX request
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        }
                    });

                    $.ajax({
                        url: '/suplemen/restore',
                        type: 'POST',
                        data: {
                            ids: allSelectedIds
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Berhasil!',
                                text: `${allSelectedIds.length} usaha berhasil direstore.`,
                                icon: 'success',
                                timer: 2000,
                                showConfirmButton: false
                            }).then(() => {
                                // Reset state
                                selectAllState = false;
                                selectedBusinessIds.clear();

                                // Refresh table
                                renderTable();

                                // Reset checkboxes
                                document.getElementById('select-all-businesses').checked =
                                    false;
                                document.getElementById('select-all-businesses')
                                    .indeterminate = false;
                                updateRestoreButton();
                            });
                        },
                        error: function(xhr, status, error) {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Gagal!',
                                text: 'Terjadi kesalahan saat merestore usaha. Silakan coba lagi.',
                                icon: 'error'
                            });
                        }
                    });
                }
            });
        };

        // Event delegation for dynamically created elements
        document.addEventListener('change', function(e) {
            console.log('🎯 EVENT DELEGATION - Change event captured:', {
                targetId: e.target.id,
                targetClasses: e.target.className,
                targetValue: e.target.value,
                targetChecked: e.target.checked
            });
            
            if (e.target.id === 'select-all-businesses') {
                console.log('🎯 EVENT: Select all checkbox detected');
                handleSelectAll(e);
            } else if (e.target.classList.contains('business-checkbox')) {
                console.log('🎯 EVENT: Individual business checkbox detected');
                handleBusinessCheckbox(e);
            } else {
                console.log('🎯 EVENT: Unhandled checkbox change');
            }
        });

        // Restore button event listener
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('restore-selected-btn').addEventListener('click', restoreSelectedBusinesses);
        });
    </script>

    <script>
        // Make entire header clickable to toggle collapse
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.getElementById('matchingInfoHeader');
            const btn = document.getElementById('matchingInfoToggle');
            if (header && btn) {
                header.addEventListener('click', function(e) {
                    // Avoid double-trigger if button itself clicked
                    if (!btn.contains(e.target)) {
                        btn.click();
                    }
                });
            }
        });
    </script>
@endpush
