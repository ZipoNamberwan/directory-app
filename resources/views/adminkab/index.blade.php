@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Dashboard'])
    <div class="container-fluid py-4">
        <div class="row mb-3">
            @foreach (['sls', 'non_sls'] as $type)
                <div class="col-xl-3 col-sm-6 mb-xl-0 mb-2">
                    <div class="card">
                        <div class="card-body p-3">
                            <div class="row">
                                <div class="col-8">
                                    <div class="numbers">
                                        <p class="text-sm mb-2 text-uppercase font-weight-bold">Pemutakhiran Direktori
                                            Sampai
                                            Level SLS</p>
                                        <h5 class="font-weight-bolder">
                                            {{ $cardData[$type]['percentage'] }}%
                                        </h5>
                                        <p class="mb-0">
                                            <span class="text-sm"><strong
                                                    class="text-{{ $type == 'sls' ? 'info' : 'success' }}">{{ $cardData[$type]['updated'] }}/{{ $cardData[$type]['total'] }}</strong>
                                                sudah dimutakhirkan.</span>
                                        </p>
                                        <p class="text-xs text-secondary mb-0">
                                            Kondisi tanggal {{ $lastUpdateDate }}
                                        </p>
                                    </div>
                                </div>
                                <div class="col-4 text-end">
                                    <div
                                        class="icon icon-shape bg-gradient-{{ $type == 'sls' ? 'info' : 'success' }} shadow-info text-center rounded-circle">
                                        <i class="ni {{ $type == 'sls' ? 'ni-money-coins' : 'ni-world' }} text-lg opacity-10"
                                            aria-hidden="true"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row mt-3">
            @foreach (['sls', 'non_sls'] as $type)
                <div class="col-md-6 col-sm-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="text-capitalize">
                                @if ($type == 'sls')
                                    Progress Pemutakhiran Direktori Sampai Level SLS
                                @else
                                    Progress Pemutakhiran Direktori Tidak Sampai Level SLS
                                @endif
                            </h6>
                        </div>
                        <div class="card-body">
                            <div style="width: 75%; margin: auto;">
                                <canvas id="{{ $type }}_chart"></canvas>
                            </div>
                            <div class="table-responsive mt-4">
                                <table class="table align-items-center">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                                Identitas Wilayah
                                            </th>
                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                                Sudah Diupdate
                                            </th>
                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                                Total Direktori
                                            </th>
                                            <th
                                                class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                                Progres Pencacahan
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tableData[$type]['regency'] as $reg)
                                            <tr>
                                                <td>
                                                    <a href="/report/kec/{{ $reg->regency->long_code }}">
                                                        <div class="d-flex gap-3 align-items-center">
                                                            <i class="fas fa-square-arrow-up-right text-lg opacity-10"
                                                                aria-hidden="true">
                                                            </i>
                                                            <div>
                                                                <p class="text-xs text-secondary mb-0">
                                                                    [{{ $reg->regency->long_code }}]
                                                                </p>
                                                                <h6 class="mb-0 text-sm">{{ $reg->regency->name }}</h6>
                                                            </div>
                                                        </div>
                                                    </a>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <p class="text-xs text-secondary mb-0">
                                                        {{ $reg->updated }}
                                                    </p>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <p class="text-xs text-secondary mb-0">
                                                        {{ $reg->total }}
                                                    </p>
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    <h6 class="mb-0 text-sm">{{ $reg->percentage }}</h6>
                                                </td>
                                            </tr>
                                        @endforeach
                                        <tr>
                                            <td>
                                                <p class="text-xs text-secondary mb-0">
                                                    [{{ $tableData[$type]['province']['code'] }}]
                                                </p>
                                                <h6 class="mb-0 text-sm">{{ $tableData[$type]['province']['name'] }}</h6>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <p class="text-xs text-secondary mb-0">
                                                    {{ $tableData[$type]['province']['updated'] }}
                                                </p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <p class="text-xs text-secondary mb-0">
                                                    {{ $tableData[$type]['province']['total'] }}
                                                </p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <h6 class="mb-0 text-sm">
                                                    {{ $tableData[$type]['province']['percentage'] }}
                                                </h6>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @include('layouts.footers.auth.footer')
    @endsection

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>
        <script src="/vendor/datatables/dataTables.min.js"></script>
        <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

        <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
        <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

        <script src="/vendor/chart.js/chart.js"></script>
        <script src="/vendor/chart.js/chartjs-plugin-datalabels@2.0.0"></script>

        <script>
            [{
                    selector: '#subdistrict',
                    placeholder: 'Filter Kecamatan'
                },
                {
                    selector: '#village',
                    placeholder: 'Filter Desa'
                },
                {
                    selector: '#sls',
                    placeholder: 'Filter SLS'
                },
                {
                    selector: '#status',
                    placeholder: 'Filter Status'
                },
                {
                    selector: '#assignment',
                    placeholder: 'Filter Assingment'
                },
            ].forEach(config => {
                $(config.selector).select2({
                    placeholder: config.placeholder,
                    allowClear: true,
                });
            });

            $('#subdistrict').on('change', function() {
                loadVillage(null, null);
                renderTable();
            });
            $('#village').on('change', function() {
                loadSls(null, null);
                renderTable();
            });
            $('#sls').on('change', function() {
                renderTable();
            });
            $('#status').on('change', function() {
                renderTable();
            });
            $('#assignment').on('change', function() {
                renderTable();
            });

            function getFilterUrl(filter) {
                var filterUrl = ''
                var e = document.getElementById(filter);
                var filterselected = e.options[e.selectedIndex];
                if (filterselected != null) {
                    var filterid = filterselected.value
                    if (filterid != 0) {
                        filterUrl = `&${filter}=` + filterid
                    }
                }

                return filterUrl
            }

            function renderTable() {
                filterUrl = ''
                filterTypes = ['status', 'subdistrict', 'village', 'sls', 'assignment']
                filterTypes.forEach(f => {
                    filterUrl += getFilterUrl(f)
                });

                table.ajax.url('/sls-directory/data?' + filterUrl).load();
            }

            function loadVillage(subdistrictid = null, selectedvillage = null) {
                let id = $('#subdistrict').val();
                if (subdistrictid != null) {
                    id = subdistrictid;
                }
                $('#village').empty();
                $('#village').append(`<option value="0" disabled selected>Processing...</option>`);
                if (id != null) {
                    $.ajax({
                        type: 'GET',
                        url: '/desa/' + id,
                        success: function(response) {

                            $('#village').empty();
                            $('#village').append(
                                `<option value="0" disabled selected> -- Filter Desa -- </option>`);
                            $('#sls').empty();
                            $('#sls').append(`<option value="0" disabled selected> -- Filter SLS -- </option>`);
                            response.forEach(element => {
                                if (selectedvillage == String(element.id)) {
                                    $('#village').append('<option value=\"' + element.id + '\" selected>' +
                                        '[' + element.short_code + '] ' + element.name + '</option>');
                                } else {
                                    $('#village').append('<option value=\"' + element.id + '\">' + '[' +
                                        element.short_code + '] ' + element.name + '</option>');
                                }
                            });
                        }
                    });
                } else {
                    $('#village').empty();
                    $('#village').append(`<option value="0" disabled> -- Filter Desa -- </option>`);
                    $('#sls').empty();
                    $('#sls').append(`<option value="0" disabled> -- Filter SLS -- </option>`);
                }
            }

            function loadSls(villageid = null, selectedsls = null) {
                let id = $('#village').val();
                if (villageid != null) {
                    id = villageid;
                }

                $('#sls').empty();
                $('#sls').append(`<option value="0" disabled selected>Processing...</option>`);
                if (id != null) {
                    $.ajax({
                        type: 'GET',
                        url: '/sls/' + id,
                        success: function(response) {

                            $('#sls').empty();
                            $('#sls').append(`<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                            response.forEach(element => {
                                if (selectedsls == String(element.id)) {
                                    $('#sls').append('<option value=\"' + element.id + '\" selected>' +
                                        '[' + element.short_code + '] ' + element.name + '</option>');
                                } else {
                                    $('#sls').append('<option value=\"' + element.id + '\">' +
                                        '[' + element.short_code + '] ' + element.name + '</option>');
                                }
                            });
                        }
                    });
                } else {
                    $('#sls').empty();
                    $('#sls').append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
                }
            }

            let table = new DataTable('#myTable', {
                order: [],
                serverSide: true,
                processing: true,
                // deferLoading: 0,
                ajax: {
                    url: '/sls-directory/data',
                    type: 'GET',
                },
                responsive: true,
                columns: [{
                        responsivePriority: 1,
                        width: "10%",
                        data: "name",
                        type: "text",
                    },
                    {
                        responsivePriority: 2,
                        width: "10%",
                        data: "sls",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `<div class="my-1"> 
                        <p style='font-size: 0.7rem' class='text-secondary mb-0'>${data.long_code}</p>                    
                        <p style='font-size: 0.7rem' class='text-secondary mb-0'>${row.subdistrict.name}</p>                                        
                        <p style='font-size: 0.7rem' class='text-secondary mb-0'>${row.village.name}</p>                                        
                        <p style='font-size: 0.7rem' class='text-secondary mb-0'>${row.sls.name}</p>                                        
                    </div>`
                            }
                            return data.id
                        }
                    },
                    {
                        responsivePriority: 3,
                        width: "10%",
                        data: "status",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return '<p class="mb-0"><span class="badge bg-gradient-' + data.color + '">' +
                                    data.name + '</span></p>';
                            }
                            return data.id;
                        }
                    },
                    {
                        responsivePriority: 4,
                        width: "10%",
                        data: "pcl",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (data == null) {
                                    return `<p style='font-size: 0.7rem' class='text-secondary mb-0'>-</p>`;
                                } else {
                                    return `<p style='font-size: 0.7rem' class='text-secondary mb-0'>${data.firstname}</p>`;
                                }
                            }
                            return data.id;
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
        </script>

        <script>
            function createChart(elementId, labels, data) {
                var ctx = document.getElementById(elementId).getContext('2d');
                return new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Progres Pemutakhiran',
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
                                    text: 'Persentase'
                                }
                            }
                        },
                        plugins: [ChartDataLabels]
                    }
                });
            }

            // Create charts
            createChart('sls_chart', @json($chartData['sls']['dates']), @json($chartData['sls']['data']));
            createChart('non_sls_chart', @json($chartData['non_sls']['dates']), @json($chartData['non_sls']['data']));
        </script>
    @endpush
