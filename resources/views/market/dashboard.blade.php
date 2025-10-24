@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .marquee-container {
            width: 100%;
            overflow: hidden;
            position: relative;
            background-color: #e0f0ff;
            border-left: 5px solid #2196f3;
            height: 2rem;
        }

        .marquee-text {
            display: inline-block;
            white-space: nowrap;
            position: absolute;
            will-change: transform;
            animation: marquee-left 20s linear infinite;
            font-size: 0.875rem;
            color: #0d47a1;
            padding: 0 1rem;
            line-height: 2rem;
        }

        @keyframes marquee-left {
            0% {
                transform: translateX(100%);
            }

            100% {
                transform: translateX(-100%);
            }
        }

        .custom-note {
            background-color: #e0f0ff;
            /* light blue background */
            border-left: 6px solid #007bff;
            /* bold blue border */
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            color: #004085;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .custom-note strong {
            margin-bottom: 5px;
        }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Progres'])
    <div class="container-fluid py-4">
        <div class="row mb-3">
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-2">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-2 text-uppercase font-weight-bold">Pemutakhiran Direktori Sentra
                                        Ekonomi
                                    </p>
                                    <h5 class="font-weight-bolder">

                                    </h5>
                                    <p class="mb-0">
                                        <span class="text-sm"><strong
                                                class="text-success">{{ $latestTotalBusiness }}</strong>
                                            usaha sentra ekonomi sudah dimutakhirkan.</span>
                                    </p>
                                    <p class="text-xs text-secondary mb-0">
                                        Kondisi tanggal: {{ $updateDate }} {{ $updateTime }}
                                    </p>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-success shadow-info text-center rounded-circle">
                                    <i class="ni ni-world text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-12 col-sm-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h5 class="text-capitalize">
                            Report Jumlah Usaha Berdasarkan Wilayah
                            <span class="badge bg-gradient-success text-white ms-2 px-2 py-1"
                                style="font-size: 0.65rem; vertical-align: middle;">BARU</span>
                        </h5>
                        <p class="text-sm mb-1">Report ini menunjukkan jumlah tagging menurut wilayah, bisa difilter
                            berdasarkan kabupaten, kecamatan, desa dan sls. Menu download report juga sudah tersedia dengann
                            menekan tombol download di bawah ini.</p>
                        <p class="text-sm mb-1">Report ini hanya diupdate sehari sekali, mengikuti jadwal matching penentuan
                            wilayah sls.</p>
                        <p class="text-sm mb-3"><strong>Report tidak realtime</strong>, report akan diupdate pada jam
                            <strong>06.00 pagi</strong>
                        </p>
                        <a href="/pasar-dashboard/download" class="btn btn-primary btn-lg ms-auto p-2 m-0" role="button">
                            <span class="btn-inner--icon"><i class="fas fa-download"></i></span>
                            <span class="ml-3 btn-inner--text">Download Report Di Sini</span>
                        </a>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            <div class="col-12">
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
                                        <select style="width: 100%;" id="subdistrict" name="subdistrict"
                                            class="form-control" data-toggle="select">
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
                                </div>
                            </div>
                            <div class="col-12">
                                <div id="data-table"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 col-sm-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="text-capitalize">
                            Report Jumlah Usaha Berdasarkan Satker
                        </h6>
                        <p class="text-sm mb-1">Report ini menunjukkan jumlah tagging menurut satker atau yang berarti
                            tagging yang dilakukan oleh petugas suatu satker, terlepas di manapun petugas tsb tagging.</p>
                        <p class="text-sm mb-1"><strong>Report tidak realtime</strong>, report akan diupdate pada jam
                            <strong>01.00 pagi</strong>
                        </p>
                        <p class="text-sm">Kondisi tanggal: <strong>{{ $updateDate }} {{ $updateTime }}</strong></p>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            @hasrole('adminprov')
                                <div class="col-md-4">
                                    <label class="form-control-label">Satker <span class="text-danger">*</span></label>
                                    <select id="organizationGraph" class="form-control" data-toggle="select">
                                        <option value="all" selected>
                                            Total Satu Provinsi
                                        </option>
                                        @foreach ($organizations as $organization)
                                            <option value="{{ $organization->id }}"
                                                {{ old('organization') == $organization->id ? 'selected' : '' }}>
                                                [{{ $organization->short_code }}] {{ $organization->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endhasrole
                        </div>

                        <div style="position: relative; width: 100%; min-height: 300px;">
                            <!-- Loader -->
                            <div id="chart-loader"
                                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 14px; color: #666; display: none;">
                                ⏳ Loading...
                            </div>

                            <!-- Chart -->
                            <canvas id="proggress_chart"></canvas>
                        </div>

                        <table id="totalTable" class="align-items-center text-sm mt-6">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Kabupaten
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Usaha Sentra Ekonomi
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Usaha Suplemen
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($reportTotalByRegency as $report)
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <div>
                                                    <p class="text-xs text-secondary mb-0 mt-1">
                                                        [{{ $report['organization_id'] }}]
                                                    </p>
                                                    <h6 class="text-sm mb-1">{{ $report['organization_name'] }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">{{ $report['market_uploaded'] }}</td>
                                        <td class="text-center">{{ $report['supplement_uploaded'] }}</td>
                                        <td class="text-center">{{ $report['total_uploaded'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>

            <div class="col-md-12 col-sm-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-capitalize">
                                    Report Jumlah Usaha Berdasarkan Petugas
                                </h6>
                                <p class="text-sm">Kondisi tanggal: {{ $updateDate }} {{ $updateTime }}</p>
                            </div>
                            <a href="/pasar-dashboard/download" class="btn btn-primary btn-lg ms-auto p-2 m-0"
                                role="button">
                                <span class="ml-3 btn-inner--text">Download</span>
                            </a>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            @hasrole('adminprov')
                                <div class="col-md-12">
                                    <label class="form-control-label">Satker <span class="text-danger">*</span></label>
                                    <select id="organizationUser" class="form-control" data-toggle="select">
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
                        </div>
                        <table id="userTable" class="align-items-center text-sm mt-2">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Nama Petugas
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Sentra Ekonomi
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Suplemen
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Total
                                    </th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @include('layouts.footers.auth.footer')
    </div>
@endsection

@push('js')
    <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="/vendor/select2/select2.min.js"></script>

    <script src="/vendor/chart.js/chart.js"></script>
    <script src="/vendor/chart.js/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="/vendor/tabulator/tabulator.min.js"></script>

    <script src="/vendor/datatables/dataTables.min.js"></script>
    <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

    <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

    <script>
        const selectConfigs = [{
                selector: '#organizationUser',
                placeholder: 'Pilih Satker'
            },
            {
                selector: '#organizationGraph',
                placeholder: 'Pilih Satker'
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
            '#organizationUser': () => {
                renderTable('user', userTable);
            },
            '#organizationGraph': () => {
                renderChart();
            },
            '#regency': () => {
                loadSubdistrict(null, null);
                renderTable('regency', areaTable);
            },
            '#subdistrict': () => {
                loadVillage(null, null);
                renderTable('subdistrict', areaTable);
            },
            '#village': () => {
                renderTable('village', areaTable);
            },
        };

        function loadSubdistrict(regencyid = null, selectedvillage = null) {

            let regencySelector = `#regency`;
            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;

            let id = $(regencySelector).val();
            if (regencyid != null) {
                id = regencyid;
            }

            $(subdistrictSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/kec/' + id,
                    success: function(response) {
                        $(subdistrictSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Kecamatan -- </option>`);
                        $(villageSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Desa -- </option>`);

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
            }
        }

        function loadVillage(subdistrictid = null, selectedvillage = null) {

            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;

            let id = $(subdistrictSelector).val();
            if (subdistrictid != null) {
                id = subdistrictid;
            }

            $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/desa/' + id,
                    success: function(response) {
                        $(villageSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Desa -- </option>`);
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
            }
        }

        Object.entries(eventHandlers).forEach(([selector, handler]) => {
            $(selector).on('change', handler);
        });

        function getFilterUrlByPrefix(prefix) {
            let filterUrl = '';
            let filterTypes = ['organization', 'marketType']; // Adjust if needed

            filterTypes.forEach(filter => {
                let elementId = prefix ? `${filter}${prefix}` : filter;
                let e = document.getElementById(elementId);
                if (e) {
                    let selectedValue = e.value;
                    if (selectedValue && selectedValue !== '0') {
                        filterUrl += `&${filter}=${selectedValue}`;
                    }
                }
            });

            return filterUrl;
        }

        function renderTable(tableType, tableInstance) {
            if (tableType == 'regency' || tableType == 'subdistrict' || tableType == 'village') {
                // Define fallback hierarchy with selectors and area types
                const areaHierarchy = [{
                        type: 'village',
                        selector: '#village'
                    },
                    {
                        type: 'subdistrict',
                        selector: '#subdistrict'
                    },
                    {
                        type: 'regency',
                        selector: '#regency'
                    },
                    {
                        type: 'province',
                        selector: null,
                        defaultId: '1'
                    }
                ];

                // Find starting index based on tableType
                const startIndex = areaHierarchy.findIndex(area => area.type === tableType);

                // Get area type and ID with fallback logic
                let areaType, areaId;
                for (let i = startIndex; i < areaHierarchy.length; i++) {
                    const area = areaHierarchy[i];

                    if (area.selector) {
                        const value = $(area.selector).val();
                        if (value && value !== '0') {
                            areaType = area.type;
                            areaId = value;
                            break;
                        }
                    } else {
                        // Default case (province)
                        areaType = area.type;
                        areaId = area.defaultId;
                        break;
                    }
                }

                let ajaxUrl = `/pasar-dashboard/area?areaType=${areaType}&areaId=${areaId}&date={{ $date }}`;
                tableInstance.setData(ajaxUrl);
            } else {
                const urlPrefix = {
                    'market': 'Market',
                    'regency': 'Regency',
                    'user': 'User'
                } [tableType];

                let filterUrl = getFilterUrlByPrefix(urlPrefix);

                tableInstance.ajax.url(`/pasar-dashboard/${tableType}/data/{{ $date }}?` + filterUrl).load();
            }
        }
    </script>

    <script>
        let areaTable = new Tabulator("#data-table", {
            height: "800px",
            layout: "fitColumns",
            ajaxURL: "pasar-dashboard/area?areaType=province&areaId=1&date={{ $date }}",
            paginationSize: 20,
            placeholder: "Tidak ada usaha yang ditemukan",
            textDirection: "auto",
            ajaxResponse: function(url, params, response) {
                return response.data;
            },
            columns: [{
                    title: "Nama Wilayah",
                    field: "name",
                    responsive: 0,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        let isTotal = row.is_total_row;
                        let nameDisplay = isTotal ?
                            `<strong style="color:#2196F3;font-size:1.1em;">${row.name}</strong>` :
                            `[${row.id}] ${row.name}`;

                        return `<div class="d-flex gap-3 align-items-center">
                            <h6 class="text-sm mb-1 ${isTotal ? "font-weight-bold" : ""}" 
                                style="${isTotal ? "font-size:1.1em;" : ""}">
                                ${nameDisplay}
                            </h6>
                        </div>`;
                    },
                },
                {
                    title: "Sentra Ekonomi",
                    field: "market_total",
                    hozAlign: "center",
                    headerHozAlign: "center",
                    responsive: 3,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        let isTotal = row.is_total_row;
                        let style = isTotal ?
                            "font-weight:bold;color:#2196F3;font-size:1.1em;" :
                            "font-weight-bold";
                        return `<div style="${style}">${$("<div>").text(cell.getValue()).html()}</div>`;
                    },
                },
                {
                    title: "Suplemen",
                    field: "supplement_total",
                    hozAlign: "center",
                    headerHozAlign: "center",
                    responsive: 2,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        let isTotal = row.is_total_row;
                        let style = isTotal ?
                            "font-weight:bold;color:#2196F3;font-size:1.1em;" :
                            "font-weight-bold";
                        return `<div style="${style}">${$("<div>").text(cell.getValue()).html()}</div>`;
                    },
                },
                {
                    title: "Total",
                    field: "total",
                    hozAlign: "center",
                    headerHozAlign: "center",
                    responsive: 2,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        let isTotal = row.is_total_row;
                        let total = isTotal ?
                            row.total :
                            Number(row.market_total) + Number(row.supplement_total);
                        let style = isTotal ?
                            "font-weight:bold;color:#2196F3;font-size:1.1em;" :
                            "font-weight-bold";
                        return `<div style="${style}">${total}</div>`;
                    },
                },
            ],
        });
    </script>

    <script>
        let totalTable = new DataTable('#totalTable', {
            responsive: true,
            ordering: true,
            paging: false,
            searching: false,
            info: false,
            pageLength: 39,
            lengthChange: false,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                },
                {
                    responsivePriority: 3,
                    width: "5%",
                },
                {
                    responsivePriority: 3,
                    width: "5%",
                },
                {
                    responsivePriority: 2,
                    width: "5%",
                },
            ],
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            }
        });

        let regencyTable = new DataTable('#regencyTable', {
            responsive: true,
            ordering: true,
            paging: false,
            searching: false,
            info: false,
            pageLength: 39,
            lengthChange: false,
            columns: [{
                    data: 'organization',
                    responsivePriority: 1,
                    width: "10%",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return `
                            <div class="d-flex gap-3 align-items-center">
                                <div>
                                    <p class="text-xs text-secondary mb-0 mt-1">
                                    [${data.id}]
                                    </p>
                                    <h6 class="text-sm mb-1">${data.name}</h6>
                                </div>
                            </div>
                                `
                        }
                        return data
                    }
                },
                {
                    data: 'total_market',
                    responsivePriority: 3,
                    width: "5%",
                    className: 'text-center'
                },
                {
                    data: 'target',
                    responsivePriority: 3,
                    width: "5%",
                    className: 'text-center'
                },
                {
                    data: 'non_target',
                    responsivePriority: 3,
                    width: "5%",
                    className: 'text-center'
                },
                {
                    data: 'not_start',
                    responsivePriority: 3,
                    width: "5%",
                    className: 'text-center'
                },
                {
                    data: 'on_going',
                    responsivePriority: 3,
                    width: "5%",
                    className: 'text-center'
                },
                {
                    data: 'done',
                    responsivePriority: 3,
                    width: "5%",
                    className: 'text-center'
                },
                {
                    data: 'market_have_business',
                    responsivePriority: 3,
                    width: "5%",
                    className: 'text-center'
                },
                {
                    data: 'uploaded',
                    responsivePriority: 2,
                    width: "5%",
                    className: 'text-center'
                },
            ],
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            }
        });

        let userTable = new DataTable('#userTable', {
            order: [],
            serverSide: true,
            processing: true,
            pageLength: 50,
            // deferLoading: 0,
            ajax: {
                url: '/pasar-dashboard/user/data/{{ $date }}',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "user.firstname",
                    type: "text",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "market",
                    className: 'text-center'
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "supplement",
                    className: 'text-center'
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "total",
                    className: 'text-center'
                },
            ],
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            }
        });

        let marketTable = new DataTable('#marketTable', {
            order: [],
            serverSide: true,
            processing: true,
            pageLength: 50,
            // deferLoading: 0,
            ajax: {
                url: '/pasar-dashboard/market/data/{{ $date }}',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "market.name",
                    type: "text",
                },
                {
                    responsivePriority: 3,
                    width: "5%",
                    data: "market_type.name",
                    type: "text",
                },
                {
                    responsivePriority: 6,
                    width: "10%",
                    data: "market",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            // Safely get IDs for area code
                            const regencyId = data.regency && data.regency.id ? data.regency.id : '';
                            const subdistrictId = data.subdistrict && data.subdistrict.short_code ? data
                                .subdistrict.short_code : '';
                            const villageId = data.village && data.village.short_code ? data.village
                                .short_code : '';
                            const areaCode = [regencyId, subdistrictId, villageId].filter(Boolean).join('');

                            const regencyName = data.regency && data.regency.name ? data.regency.name : '';
                            const subdistrictName = data.subdistrict && data.subdistrict.name ? data
                                .subdistrict.name : '';
                            const villageName = data.village && data.village.name ? data.village.name : '';

                            // Build location string without trailing/extra commas
                            let locationParts = [];
                            if (regencyName) locationParts.push(regencyName);
                            if (subdistrictName) locationParts.push(subdistrictName);
                            if (villageName) locationParts.push(villageName);
                            const locationString = locationParts.join(', ');
                            return `
                                    <p class="text-sm text-secondary mb-0">[${areaCode}] ${locationString}</p>
                                `
                        }
                        return data
                    }
                },
                {
                    responsivePriority: 5,
                    width: "10%",
                    data: "target_category",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            if (data == 'target') {
                                return '<span class="badge badge-sm bg-gradient-success">Target</span>';
                            } else if (data == 'non target') {
                                return '<span class="badge badge-sm bg-gradient-danger">Non Target</span>';
                            } else {
                                return data;
                            }
                        }
                        return data
                    }
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "completion_status",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            if (row.target_category == 'target') {
                                if (data == 'not start') {
                                    return '<span class="badge badge-sm bg-gradient-secondary">Belum Dimulai</span>';
                                } else if (data == 'on going') {
                                    return '<span class="badge badge-sm bg-gradient-warning">Sedang Dikerjakan</span>';
                                } else if (data == 'done') {
                                    return '<span class="badge badge-sm bg-gradient-success">Sudah Selesai</span>';
                                } else {
                                    return data;
                                }
                            } else {
                                return '-'
                            }
                        }
                        return data
                    }
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "uploaded",
                    className: 'text-center'
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
        function createChart(elementId, labels, data) {
            var ctx = document.getElementById(elementId).getContext('2d');
            return new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Progres Pemutakhiran Usaha Total (Sentra Ekonomi + Suplemen)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        data: data
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        datalabels: {
                            display: true,
                            align: 'top',
                            anchor: 'end',
                            formatter: (value) => value,
                            font: {
                                weight: 'bold'
                            }
                        },
                        title: {
                            display: true,
                            text: (ctx) => 'Point Style: ' + ctx.chart.data.datasets[0].pointStyle,
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        },
                        y: {
                            display: true,
                            title: {
                                display: true,
                                text: 'Jumlah'
                            }
                        }
                    },
                    plugins: [ChartDataLabels]
                }
            });
        }

        progressChart = createChart('proggress_chart', @json($chartData['dates']), @json($chartData['data']));

        function renderChart() {
            const regencyId = $('#organizationGraph').val();
            const loader = document.getElementById("chart-loader");

            if (regencyId != null) {
                loader.style.display = "block";
                fetch(`/pasar-dashboard/graph/data/${regencyId}`)
                    .then(response => response.json())
                    .then(result => {
                        progressChart.data.labels = result['dates'];
                        progressChart.data.datasets[0].data = result['data'];
                        progressChart.update();
                    })
                    .catch(err => console.error("Error fetching chart data:", err))
                    .finally(() => {
                        // Hide loader once done
                        loader.style.display = "none";
                    });
            }
        }
    </script>
@endpush
