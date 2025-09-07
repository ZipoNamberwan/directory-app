@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .anomaly-item {
            display: flex;
            align-items: center;
            align-content: center;
            font-size: 12px;
        }

        .anomaly-type {
            margin-right: 8px;
            font-weight: 500;
            color: #333;
        }

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

        .edit-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .edit-btn:hover {
            background: #0056b3;
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

            console.log(filterUrl)

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
                <div class="anomaly-item">
                    <span class="anomaly-type" title="${anomaly.name}">${anomaly.type}</span>
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
                        let row = cell.getRow().getData();
                        return `
                        <form id="formdelete${row.id}" name="formdelete${row.id}" 
                            onSubmit="deleteBusiness('${row.id}','${row.name}')" 
                            class="d-inline" action="/suplemen/${row.id}" method="POST">
                            @csrf
                            @method('delete')
                            <button class="btn btn-outline-danger btn-sm p-1" type="submit">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>`;;
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
