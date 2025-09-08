@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Custom status circle styles - not available in Bootstrap */
        .status-circle {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-notconfirmed {
            background-color: #ff9800;
        }

        .status-fixed {
            background-color: #4caf50;
        }

        .status-dismissed {
            background-color: #2196f3;
        }

        .status-other {
            background-color: #f44336;
        }

        /* Custom badge colors */
        .badge.notconfirmed {
            background-color: #ff9800;
        }

        .badge.fixed {
            background-color: #4caf50;
        }

        .badge.dismissed {
            background-color: #2196f3;
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

        /* Fix button hover issue - remove white background on hover for radio button labels */
        .btn-outline-success:hover,
        .btn-outline-info:hover,
        .btn-check+.btn-outline-success:hover,
        .btn-check+.btn-outline-info:hover {
            background-color: transparent !important;
            border-color: currentColor !important;
        }

        .btn-outline-success:hover,
        .btn-check+.btn-outline-success:hover {
            color: #4caf50 !important;
        }

        .btn-outline-info:hover,
        .btn-check+.btn-outline-info:hover {
            color: #2196f3 !important;
        }

        /* Ensure checked state maintains proper styling with matching badge colors */
        .btn-check:checked+.btn-outline-success,
        .btn-check:checked+.btn-outline-info {
            background-color: transparent !important;
        }

        .btn-check:checked+.btn-outline-success {
            background-color: #4caf50 !important;
            color: white !important;
            border-color: #4caf50 !important;
        }

        .btn-check:checked+.btn-outline-info {
            background-color: #2196f3 !important;
            color: white !important;
            border-color: #2196f3 !important;
        }

        /* Custom button colors to match badge colors */
        .btn-outline-success {
            color: #4caf50 !important;
            border-color: #4caf50 !important;
        }

        .btn-outline-info {
            color: #2196f3 !important;
            border-color: #2196f3 !important;
        }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'List Anomali'])
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
                    <h5 class="text-capitalize">Daftar Anomali</h5>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
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
                    <div class="col-md-3">
                        <label class="form-control-label">Jenis Usaha</label>
                        <select id="businessType" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Jenis Usaha -- </option>
                            <option value="market">Sentra Ekonomi</option>
                            <option value="supplement">Suplemen</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label" for="keyword">Cari</label>
                        <input type="text" name="keyword" class="form-control" id="keyword"
                            placeholder="Cari By Keyword">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-control-label">Jenis Anomali</label>
                        <select id="anomalyType" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Jenis Anomali -- </option>
                            @foreach ($anomalyTypes as $anomalyType)
                                <option value="{{ $anomalyType->id }}"
                                    {{ old('anomalyType') == $anomalyType->id ? 'selected' : '' }}>
                                    [{{ $anomalyType->code }}] {{ $anomalyType->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Status Anomali</label>
                        <select id="anomalyStatus" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Status Anomali -- </option>
                            <option value="notconfirmed">Belum Dikonfirmasi</option>
                            <option value="fixed">Sudah Diperbaiki</option>
                            <option value="dismissed">Sesuai Kondisi Lapangan</option>
                        </select>
                    </div>
                </div>
                <div class="row">
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
                        <div class="col-md-12">
                            <p class="mb-2 text-muted small">
                                Jumlah anomali yang difilter: <span id="total-records" class="fw-bold">0</span>
                            </p>
                        </div>
                    </div>
                    <div id="data-table"></div>
                </div>
            </div>
        </div>

        @include('layouts.footers.auth.footer')
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Perbaiki Anomali</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Loading Indicator -->
                    <div id="modal-loading" class="d-none">
                        <div class="d-flex justify-content-center align-items-center" style="min-height: 200px;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Success Message -->
                    <div id="modal-success" class="alert alert-success d-none">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="success-message">Anomali berhasil diperbaiki!</span>
                    </div>

                    <!-- Business Information -->
                    <div id="modal-content">
                        <div class="card mb-3">
                            <div class="card-header pb-0">
                                <h6 class="mb-0">Informasi Usaha</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-lg-6 col-md-12">
                                        <div class="mb-2">
                                            <strong>Nama Usaha:</strong>
                                            <span id="business-name" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Tipe Usaha:</strong>
                                            <span id="business-type" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                        <div class="mb-2" id="market-name-container" style="display: none;">
                                            <strong>Nama Pasar:</strong>
                                            <span id="market-name" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Deskripsi:</strong>
                                            <span id="business-description" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Status:</strong>
                                            <span id="business-status" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Alamat:</strong>
                                            <span id="business-address" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12">
                                        <div class="mb-2">
                                            <strong>Sektor:</strong>
                                            <span id="business-sector" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Pemilik:</strong>
                                            <span id="business-owner" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Petugas:</strong>
                                            <span id="user-name" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                        <div class="mb-2">
                                            <strong>Satker:</strong>
                                            <span id="organization-name" class="d-block d-sm-inline ms-sm-2"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Anomalies Section -->
                        <div class="card">
                            <div class="card-header pb-0">
                                <h6 class="mb-0">Daftar Anomali</h6>
                            </div>
                            <div class="card-body">
                                <form id="anomalyForm">
                                    <input type="hidden" id="business-id" name="business_id" value="">
                                    <div id="anomalies-container">
                                        <!-- Anomalies will be populated here -->
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="save-anomalies">
                        <span class="btn-text">Simpan Perubahan</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
    <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="/vendor/select2/select2.min.js"></script>
    <script src="/vendor/sweetalert2/sweetalert2.js"></script>
    <script src="/vendor/tabulator/tabulator.min.js"></script>

    <script src="/vendor/tabulator/tabulator.min.js"></script>

    <script>
        const selectConfigs = [{
                selector: '#organization',
                placeholder: 'Pilih Satker'
            },
            {
                selector: '#anomalyType',
                placeholder: 'Pilih Jenis Anomali'
            },
            {
                selector: '#anomalyStatus',
                placeholder: 'Pilih Status Anomali'
            },
            {
                selector: '#businessType',
                placeholder: 'Pilih Jenis Usaha'
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
            '#anomalyType': () => {
                renderTable()
            },
            '#anomalyStatus': () => {
                renderTable()
            },
            '#businessType': () => {
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

        Object.entries(eventHandlers).forEach(([selector, handler]) => {
            $(selector).on('change', handler);
        });

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
            filterTypes = ['organization', 'anomalyType', 'anomalyStatus', 'businessType', 'keyword', 'regency',
                'subdistrict', 'village', 'sls',
            ];
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            table.setData('/anomali/data?' + filterUrl);
        }
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

        function canDelete($id) {
            return true;
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
    </script>

    <script>
        // Global table variable
        let table;

        // Define column configurations for different modes
        const getColumnConfig = (mode) => {
            const baseColumns = [{
                    title: "Nama Usaha",
                    field: "business.name",
                    responsive: 0,
                    formatter: function(cell) {
                        return `<div class="text-wrap font-weight-bold">${$("<div>").text(cell.getValue()).html()}</div>`;
                    }
                },
                {
                    title: "Tipe Usaha",
                    field: "type",
                    responsive: 0,
                    formatter: function(cell) {
                        let value = cell.getValue();

                        let displayValue = value;
                        if (value === "App\\Models\\SupplementBusiness") {
                            displayValue = "Suplemen    ";
                        } else if (value === "App\\Models\\MarketBusiness") {
                            displayValue = "Sentra Ekonomi";
                        } else {
                            displayValue = "Lainnya";
                        }

                        return `<div class="text-wrap small">${$("<div>").text(displayValue).html()}</div>`;
                    }
                },
                {
                    title: "Detail",
                    field: "detail",
                    responsive: 1,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        let html = `<div class="mb-3 small text-wrap lh-sm">`;

                        if (row.business.owner) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Pemilik:</span> <span class="fw-semibold text-dark">${row.business.owner}</span></div>`;
                        }
                        if (row.business.status) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Status:</span> <span class="fw-semibold text-dark">${row.business.status}</span></div>`;
                        }
                        if (row.business.address) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Alamat:</span> <span class="fw-semibold text-dark">${row.business.address}</span></div>`;
                        }
                        if (row.business.description) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Deskripsi:</span> <span class="fw-semibold text-dark">${truncateText(row.business.description, 60)}</span></div>`;
                        }
                        if (row.business.sector) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Sektor:</span> <span class="fw-semibold text-dark">${truncateText(row.business.sector, 40)}</span></div>`;
                        }
                        if (row.business.notes) {
                            html +=
                                `<div><span class="text-muted">Catatan:</span> <span class="fw-semibold text-dark">${row.business.notes}</span></div>`;
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
                        if (row.business.latitude && row.business.longitude) {
                            const lat = row.business.latitude;
                            const lng = row.business.longitude;
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
                        let row = cell.getRow().getData().business;

                        let areaId = "";
                        if (row.sls_id) {
                            areaId = row.sls_id;
                        } else if (row.village_id) {
                            areaId = row.village_id;
                        } else if (row.subdistrict_id) {
                            areaId = row.subdistrict_id;
                        } else if (row.regency_id) {
                            areaId = row.regency_id;
                        }

                        if (!areaId) return "-";

                        return `<div class="text-wrap lh-sm">
                    ${areaId ? `<div class="fw-semibold text-success">${toTitleCase(areaId)}</div>` : ""}
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
                    title: "Anomali",
                    field: "anomalies",
                    responsive: 2,
                    resizable: true,
                    formatter: function(cell) {
                        const anomalies = cell.getValue();
                        if (!anomalies || anomalies.length === 0) {
                            return '<span style="color: #999;">No anomalies</span>';
                        }

                        let html = '<div>';
                        anomalies.forEach(anomaly => {
                            let statusClass = 'status-other';
                            let statusLabel = anomaly.status;

                            if (anomaly.status === 'notconfirmed') {
                                statusClass = 'status-notconfirmed';
                                statusLabel = 'Belum Dikonfirmasi';
                            } else if (anomaly.status === 'fixed') {
                                statusClass = 'status-fixed';
                                statusLabel = 'Sudah Diperbaiki';
                            } else if (anomaly.status === 'dismissed') {
                                statusClass = 'status-dismissed';
                                statusLabel = 'Sesuai Kondisi Lapangan';
                            } else {
                                statusClass = 'status-other';
                                statusLabel = 'Status Lainnya';
                            }

                            html += `
                <div class="d-flex align-items-center mb-1" style="font-size: 12px;">
                    <span class="me-2 fw-medium text-dark" title="${anomaly.name}">${anomaly.type}</span>
                    <div class="status-circle ${statusClass}" title="${statusLabel}"></div>
                </div>`;
                        });
                        html += '</div>';
                        return html;
                    }
                },
                {
                    title: "Actions",
                    field: "id",
                    width: 100,
                    hozAlign: "center",
                    headerSort: false,
                    formatter: function(cell) {
                        let row = cell.getRow();
                        let rowData = row.getData();

                        // attach row object to button via event
                        let button = document.createElement("button");
                        button.className = "btn btn-success btn-sm px-2 py-1";
                        button.innerHTML = `<i class="fas fa-pencil-alt"></i>`;
                        button.onclick = function() {
                            openEditDialog(rowData);
                        };

                        return button;
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
                ajaxURL: "/anomali/data",
                progressiveLoad: "scroll",
                paginationSize: 20,
                placeholder: "Tidak ada anomali yang ditemukan",
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

    <script>
        function openEditDialog(rowData) {
            // Reset modal state
            resetModalState();

            // Populate business information
            populateBusinessInfo(rowData);

            // Populate anomalies
            populateAnomalies(rowData.anomalies);

            // Show modal
            let modal = new bootstrap.Modal(document.getElementById("editModal"));
            modal.show();
        }

        function resetModalState() {
            // Hide loading and success messages
            document.getElementById('modal-loading').classList.add('d-none');
            document.getElementById('modal-success').classList.add('d-none');
            document.getElementById('modal-content').classList.remove('d-none');

            // Reset save button
            const saveBtn = document.getElementById('save-anomalies');
            saveBtn.disabled = false;
            saveBtn.querySelector('.btn-text').textContent = 'Simpan Perubahan';
            saveBtn.querySelector('.spinner-border').classList.add('d-none');

            // Clear previous anomalies
            document.getElementById('anomalies-container').innerHTML = '';
        }

        function populateBusinessInfo(rowData) {
            const business = rowData.business;
            const user = rowData.user;
            const organization = rowData.organization;

            // Store business ID in hidden input
            document.getElementById('business-id').value = rowData.id;

            document.getElementById('business-name').textContent = business.name || '-';

            // Populate business type based on the type field
            let businessType = '-';
            if (rowData.type === "App\\Models\\SupplementBusiness") {
                businessType = "Suplemen";
            } else if (rowData.type === "App\\Models\\MarketBusiness") {
                businessType = "Sentra Ekonomi";
            } else {
                businessType = "Lainnya";
            }
            document.getElementById('business-type').textContent = businessType;

            // Handle market_name - only show if not null
            const marketNameContainer = document.getElementById('market-name-container');
            const marketNameElement = document.getElementById('market-name');
            if (business.market_name && business.market_name.trim() !== '') {
                marketNameElement.textContent = business.market_name;
                marketNameContainer.style.display = 'block';
            } else {
                marketNameContainer.style.display = 'none';
            }

            document.getElementById('business-description').textContent = business.description || '-';
            document.getElementById('business-status').textContent = business.status || '-';
            document.getElementById('business-address').textContent = business.address || '-';
            document.getElementById('business-sector').textContent = business.sector || '-';
            document.getElementById('business-owner').textContent = business.owner || '-';
            document.getElementById('user-name').textContent = user ? user.firstname : '-';
            document.getElementById('organization-name').textContent = organization ? organization.name : '-';
        }

        function populateAnomalies(anomalies) {
            const container = document.getElementById('anomalies-container');

            anomalies.forEach((anomaly, index) => {
                const anomalyHtml = createAnomalyCard(anomaly, index);
                container.insertAdjacentHTML('beforeend', anomalyHtml);

                // Add event listeners for this anomaly
                setupAnomalyEventListeners(anomaly.id, index);
            });
        }

        function createAnomalyCard(anomaly, index) {
            return `
                <div class="card mb-3 border anomaly-card" data-anomaly-id="${anomaly.id}">
                    <div class="card-header bg-light py-3 px-3">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2">
                            <div class="flex-grow-1">
                                <strong>[${anomaly.type}] ${anomaly.name}</strong>
                                <span class="badge ms-2 ${anomaly.status}">${getStatusText(anomaly.status)}</span>
                            </div>
                        </div>
                        <!-- <small class="text-muted d-block mt-1">${anomaly.description}</small> -->
                        ${anomaly.last_repaired_by_firstname ? `
                        <small class="text-muted d-block mt-2">
                            <i class="fas fa-user me-1"></i>Terakhir diperbaiki oleh: <strong>${anomaly.last_repaired_by_firstname}</strong> (${anomaly.last_repaired_by_email})
                        </small>
                        ` : ''}
                    </div>
                    <div class="card-body pt-1 pb-3 px-3">
                        <!-- Action buttons first -->
                        <div>
                            <label class="form-label"><strong>Aksi:</strong></label>
                            <div class="btn-group d-flex flex-column flex-sm-row gap-1" role="group">
                                <input type="radio" class="btn-check" name="status_${anomaly.id}" id="fixed_${index}" value="fixed" ${anomaly.status === 'fixed' ? 'checked' : ''}>
                                <label class="btn btn-outline-success btn-sm flex-fill" for="fixed_${index}">
                                    <i class="fas fa-check me-1"></i>Perbaiki
                                </label>
                                
                                <input type="radio" class="btn-check" name="status_${anomaly.id}" id="dismissed_${index}" value="dismissed" ${anomaly.status === 'dismissed' ? 'checked' : ''}>
                                <label class="btn btn-outline-info btn-sm flex-fill" for="dismissed_${index}">
                                    <i class="fas fa-times me-1"></i>Abaikan
                                </label>
                            </div>
                        </div>
                        
                        <!-- Then old value and fixed value/note -->
                        <div class="row g-3">
                            <div class="col-lg-6 col-12">
                                <label class="form-label"><strong>Nilai Lama:</strong></label>
                                <div class="form-control-plaintext border rounded p-2 bg-light text-break">
                                    ${anomaly.old_value || '-'}
                                </div>
                            </div>
                            <div class="col-lg-6 col-12">
                                <label class="form-label"><strong>Nilai Perbaikan:</strong></label>
                                <input type="text" 
                                       class="form-control fixed-value-input" 
                                       name="fixed_value_${anomaly.id}"
                                       value="${anomaly.fixed_value || ''}"
                                       ${anomaly.status === 'dismissed' || anomaly.status === 'notconfirmed' || !anomaly.status ? 'disabled' : ''}
                                       placeholder="Masukkan nilai perbaikan">
                            </div>
                        </div>
                        
                        <!-- Note field for dismissed action -->
                        <div class="row g-3 mt-2">
                            <div class="col-12">
                                <label class="form-label"><strong>Catatan (untuk aksi diabaikan):</strong></label>
                                <textarea class="form-control note-input" 
                                          name="note_${anomaly.id}"
                                          rows="3"
                                          ${anomaly.status === 'fixed' || anomaly.status === 'notconfirmed' || !anomaly.status ? 'disabled' : ''}
                                          placeholder="Masukkan alasan mengapa anomali ini diabaikan">${anomaly.note || ''}</textarea>
                            </div>
                        </div>
                        
                        <!-- Error message placeholder -->
                        <small class="text-danger mt-6 d-none" id="error_${anomaly.id}"></small>
                    </div>
                </div>
            `;
        }

        function setupAnomalyEventListeners(anomalyId, index) {
            // Listen for status changes
            const fixedRadio = document.getElementById(`fixed_${index}`);
            const dismissedRadio = document.getElementById(`dismissed_${index}`);
            const fixedValueInput = document.querySelector(`input[name="fixed_value_${anomalyId}"]`);
            const noteInput = document.querySelector(`textarea[name="note_${anomalyId}"]`);
            const anomalyCard = document.querySelector(`[data-anomaly-id="${anomalyId}"]`);

            function updateInputState() {
                if (dismissedRadio.checked) {
                    // Dismissed: enable note input, disable fixed value input
                    fixedValueInput.disabled = true;
                    fixedValueInput.value = '';
                    noteInput.disabled = false;
                } else if (fixedRadio.checked) {
                    // Fixed: enable fixed value input, disable note input
                    fixedValueInput.disabled = false;
                    noteInput.disabled = true;
                    noteInput.value = '';
                } else {
                    // No action selected - disable both input fields
                    fixedValueInput.disabled = true;
                    noteInput.disabled = true;
                }

                // Clear any previous errors
                document.getElementById(`error_${anomalyId}`).classList.add('d-none');
            }

            // Initialize state on load
            updateInputState();

            fixedRadio.addEventListener('change', updateInputState);
            dismissedRadio.addEventListener('change', updateInputState);
        }

        function getStatusText(status) {
            switch (status) {
                case 'fixed':
                    return 'Diperbaiki';
                case 'dismissed':
                    return 'Diabaikan';
                case 'notconfirmed':
                    return 'Belum Dikonfirmasi';
                default:
                    return status;
            }
        }

        // Save anomalies function
        document.getElementById('save-anomalies').addEventListener('click', function() {
            saveAnomalies();
        });

        function generateAnomalyUpdateRequestBody() {
            // Collect anomaly data
            const anomaliesData = [];
            document.querySelectorAll('.anomaly-card').forEach(card => {
                const anomalyId = card.dataset.anomalyId;
                const statusRadios = document.getElementsByName(`status_${anomalyId}`);
                const fixedValueInput = document.querySelector(`input[name="fixed_value_${anomalyId}"]`);
                const noteInput = document.querySelector(`textarea[name="note_${anomalyId}"]`);

                let selectedStatus = null;
                for (const radio of statusRadios) {
                    if (radio.checked) {
                        selectedStatus = radio.value;
                        break;
                    }
                }

                anomaliesData.push({
                    id: anomalyId,
                    status: selectedStatus,
                    fixed_value: selectedStatus === 'fixed' ? fixedValueInput.value : null,
                    note: selectedStatus === 'dismissed' ? noteInput.value : null
                });
            });

            // Get business ID from hidden input
            const businessId = document.getElementById('business-id').value;

            return {
                business_id: businessId,
                anomalies: anomaliesData
            };
        }

        function saveAnomalies() {
            const saveBtn = document.getElementById('save-anomalies');
            const modalContent = document.getElementById('modal-content');
            const modalLoading = document.getElementById('modal-loading');
            const modalSuccess = document.getElementById('modal-success');

            // Show loading state
            saveBtn.disabled = true;
            saveBtn.querySelector('.btn-text').textContent = 'Menyimpan...';
            saveBtn.querySelector('.spinner-border').classList.remove('d-none');

            // Clear previous errors
            document.querySelectorAll('[id^="error_"]').forEach(el => el.classList.add('d-none'));

            // Validate all anomalies
            if (isValidAnomalyRepair()) {
                resetSaveButton();
                return;
            }

            // Generate request body
            const requestBody = generateAnomalyUpdateRequestBody();

            fetch('/anomali/update', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(requestBody)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update the table row with new data
                        updateTableRowWithNewData(data.data);
                        showSuccessMessage(true);
                    } else {
                        // Handle validation errors
                        if (data.errors && Array.isArray(data.errors)) {
                            data.errors.forEach(error => {
                                showAnomalyError(error.anomaly_id, error.message);
                            });
                        } else {
                            console.error('Unexpected error format:', data);
                        }
                        resetSaveButton();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Terjadi kesalahan sistem. Silakan coba lagi.');
                    resetSaveButton();
                });
        }

        function updateTableRowWithNewData(updatedBusinessData) {
            // Find the row in the table by business ID
            const businessId = updatedBusinessData.id;
            
            // Get the current table data
            const currentData = table.getData();
            
            // Find the index of the row to update
            const rowIndex = currentData.findIndex(row => row.id === businessId);
            
            if (rowIndex !== -1) {
                // Update the row data in place
                table.updateRow(businessId, updatedBusinessData);
                
                // Force redraw of the updated row to refresh formatted columns
                const updatedRow = table.getRow(businessId);
                if (updatedRow) {
                    updatedRow.reformat();
                }
            } else {
                console.warn('Row not found in table for business:', businessId);
            }
        }

        function showSuccessMessage(autoHide = true) {
            const saveBtn = document.getElementById('save-anomalies');
            const modalContent = document.getElementById('modal-content');
            const modalLoading = document.getElementById('modal-loading');
            const modalSuccess = document.getElementById('modal-success');

            // Hide loading and content
            modalLoading.classList.add('d-none');
            modalContent.classList.add('d-none');

            // Show success message
            modalSuccess.classList.remove('d-none');

            // Reset save button
            saveBtn.disabled = false;
            saveBtn.querySelector('.btn-text').textContent = 'Simpan Perubahan';
            saveBtn.querySelector('.spinner-border').classList.add('d-none');

            if (autoHide) {
                // Close modal immediately
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            } else {
                // Wait 2 seconds before closing (current behavior)
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                }, 2000);
            }
        }

        function showAnomalyError(anomalyId, errorMessage) {
            const errorElement = document.getElementById(`error_${anomalyId}`);
            errorElement.textContent = errorMessage;
            errorElement.classList.remove('d-none');
        }

        function isValidAnomalyRepair() {
            let hasValidationErrors = false;

            document.querySelectorAll('.anomaly-card').forEach(card => {
                const anomalyId = card.dataset.anomalyId;
                const statusRadios = document.getElementsByName(`status_${anomalyId}`);
                const fixedValueInput = document.querySelector(`input[name="fixed_value_${anomalyId}"]`);
                const noteInput = document.querySelector(`textarea[name="note_${anomalyId}"]`);

                let selectedStatus = null;
                for (const radio of statusRadios) {
                    if (radio.checked) {
                        selectedStatus = radio.value;
                        break;
                    }
                }

                // Validation: check if no action is selected
                if (!selectedStatus) {
                    showAnomalyError(anomalyId, 'Silakan pilih aksi untuk anomali ini (Perbaiki atau Abaikan)');
                    hasValidationErrors = true;
                    return; // Skip other validations for this anomaly
                }

                // Validation: if status is 'fixed', fixed_value must not be empty
                if (selectedStatus === 'fixed') {
                    const fixedValue = fixedValueInput ? fixedValueInput.value.trim() : '';
                    if (!fixedValue || fixedValue === '') {
                        showAnomalyError(anomalyId, 'Nilai perbaikan wajib diisi ketika memilih "Perbaiki"');
                        hasValidationErrors = true;
                    }
                }
                
                // Validation: if status is 'dismissed', note must not be empty
                if (selectedStatus === 'dismissed') {
                    const noteValue = noteInput ? noteInput.value.trim() : '';
                    if (!noteValue || noteValue === '') {
                        showAnomalyError(anomalyId, 'Catatan wajib diisi ketika memilih "Abaikan"');
                        hasValidationErrors = true;
                    }
                }
            });

            return hasValidationErrors;
        }

        function resetSaveButton() {
            const saveBtn = document.getElementById('save-anomalies');
            saveBtn.disabled = false;
            saveBtn.querySelector('.btn-text').textContent = 'Simpan Perubahan';
            saveBtn.querySelector('.spinner-border').classList.add('d-none');
        }
    </script>
@endpush
