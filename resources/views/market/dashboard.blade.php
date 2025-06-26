@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
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
                        <h6 class="text-capitalize">
                            Report Jumlah Usaha Total (Sentra Ekonomi + Suplemen) Berdasarkan Kabupaten/Kota
                        </h6>
                        <p class="text-sm mb-1"><strong>Report tidak realtime</strong>, report akan diupdate pada jam
                            <strong>06.00, 12.00, 18.00,
                                22.30</strong>
                        </p>
                        <p class="text-sm">Kondisi tanggal: <strong>{{ $updateDate }} {{ $updateTime }}</strong></p>
                        <a href="/pasar-dashboard/download" class="btn btn-primary btn-lg ms-auto p-2 m-0" role="button">
                            <span class="btn-inner--icon"><i class="fas fa-download"></i></span>
                            <span class="ml-3 btn-inner--text">Download Report Di Sini</span>
                        </a>

                    </div>
                    <div class="card-body">
                        <div style="width: 75%; margin: auto;">
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
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-capitalize">
                                    Report Jumlah Usaha Berdasarkan Sentra Ekonomi
                                </h6>
                                <p class="text-sm">Kondisi tanggal: {{ $updateDate }} {{ $updateTime }}</p>
                            </div>
                            <a href="/pasar-dashboard/download" class="btn btn-primary btn-lg ms-auto p-2 m-0"
                                role="button">
                                <span class="btn-inner--icon"><i class="fas fa-download"></i></span>
                                <span class="ml-3 btn-inner--text">Download</span>
                            </a>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row mt-4">
                            <div class="col-md-4 {{-- border-secondary rounded border p-3 --}}">
                                <h5 class="card-title text-primary font-weight-bold mb-3">
                                    <i class="fas fa-filter mr-2"></i>Filter Tipe Sentra Ekonomi
                                    <span class="text-danger">*</span>
                                </h5>
                                <div class="d-flex align-items-center">
                                    <select id="marketTypeRegency" class="form-control form-control-lg border-primary"
                                        data-toggle="select">
                                        <option value="all" selected> Semua </option>
                                        @foreach ($marketTypes as $marketType)
                                            <option value="{{ $marketType->id }}"
                                                {{ old('marketType') == $marketType->id ? 'selected' : '' }}>
                                                {{ $marketType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div id="regencyLoadingIndicator" class="d-none text-center ml-2">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted mt-2 d-block">Pilih tipe sentra ekonomi untuk melihat hasil</small>
                            </div>
                        </div>

                        <table id="regencyTable" class="align-items-center text-sm">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Kabupaten
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Total Sentra Ekonomi
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Target
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Bukan Target
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Belum Dimulai
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Sedang Dikerjakan
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Sudah Selesai
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Jumlah Sentra Ekonomi dengan Muatan yang sudah Diupload Minimal 1
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Usaha yang Sudah Dimutakhirkan
                                    </th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-9 col-sm-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="text-capitalize">
                                    Report Jumlah Usaha Berdasarkan Sentra Ekonomi
                                </h6>
                                <p class="text-sm">Kondisi tanggal: {{ $updateDate }} {{ $updateTime }}</p>
                            </div>
                            <a href="/pasar-dashboard/download" class="btn btn-primary btn-lg ms-auto p-2 m-0"
                                role="button">
                                <span class="btn-inner--icon"><i class="fas fa-download"></i></span>
                                <span class="ml-3 btn-inner--text">Download</span>
                            </a>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row">
                            @hasrole('adminprov')
                                <div class="col-md-3">
                                    <label class="form-control-label">Satker <span class="text-danger">*</span></label>
                                    <select id="organizationMarket" class="form-control" data-toggle="select">
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
                                <label class="form-control-label">Filter Tipe <span class="text-danger">*</span></label>
                                <select id="marketTypeMarket" class="form-control" data-toggle="select">
                                    <option value="0" disabled selected> -- Pilih Tipe -- </option>
                                    @foreach ($marketTypes as $marketType)
                                        <option value="{{ $marketType->id }}"
                                            {{ old('marketType') == $marketType->id ? 'selected' : '' }}>
                                            {{ $marketType->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <table id="marketTable" class="align-items-center text-sm mt-2">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Nama Sentra Ekonomi
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Tipe
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Wilayah
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Status Target
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Status Penyelesaian
                                    </th>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7 text-center">
                                        Usaha yang Sudah Dimutakhirkan
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr id="regencyLoadingRow">
                                    <td colspan="9" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="sr-only">Loading...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-12 mb-3">
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
                                        Usaha yang Sudah Dimutakhirkan
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

    <script src="/vendor/datatables/dataTables.min.js"></script>
    <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

    <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

    <script>
        const selectConfigs = [{
                selector: '#marketTypeMarket',
                placeholder: 'Pilih Tipe'
            },
            {
                selector: '#marketTypeRegency',
                placeholder: 'Pilih Tipe'
            },
            {
                selector: '#organizationMarket',
                placeholder: 'Pilih Satker'
            },
            {
                selector: '#organizationUser',
                placeholder: 'Pilih Satker'
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
            '#marketTypeMarket': () => {
                renderTable('market', marketTable);
            },
            '#marketTypeRegency': () => {
                renderRegencyTable()
                // renderTable('regency', regencyTable);
            },
            '#organizationMarket': () => {
                renderTable('market', marketTable);
            },
            '#organizationUser': () => {
                renderTable('user', userTable);
            },
        };

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
            const urlPrefix = {
                'market': 'Market',
                'regency': 'Regency',
                'user': 'User'
            } [tableType];

            let filterUrl = getFilterUrlByPrefix(urlPrefix);

            tableInstance.ajax.url(`/pasar-dashboard/${tableType}/data/{{ $date }}?` + filterUrl).load();
        }

        function renderRegencyTable() {
            const marketType = $('#marketTypeRegency').val();

            $('#regencyLoadingIndicator').removeClass('d-none');

            $.ajax({
                url: `/pasar-dashboard/regency/data/{{ $date }}`,
                type: 'GET',
                data: {
                    marketType: marketType
                },
                success: function(response) {
                    regencyTable.clear().rows.add(response).draw();
                },
                error: function() {
                    alert('Gagal memuat data');
                },
                complete: function() {
                    $('#regencyLoadingIndicator').addClass('d-none');
                }
            });
        }

        renderRegencyTable()
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

        createChart('proggress_chart', @json($chartData['dates']), @json($chartData['data']));
    </script>
@endpush
