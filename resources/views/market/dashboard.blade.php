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
                                    <p class="text-sm mb-2 text-uppercase font-weight-bold">Pemutakhiran Direktori Sentra Ekonomi
                                    </p>
                                    <h5 class="font-weight-bolder">

                                    </h5>
                                    <p class="mb-0">
                                        <span class="text-sm"><strong class="text-success">{{ $totalBusiness }}</strong>
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
                            Report Jumlah Usaha Berdasarkan Kabupaten/Kota
                        </h6>
                        <p class="text-sm mb-1"><strong>Report tidak realtime</strong>, report akan diupdate pada jam
                            <strong>06.00, 12.00, 18.00,
                                22.30</strong>
                        </p>
                        <p class="text-sm">Kondisi tanggal: <strong>{{ $updateDate }} {{ $updateTime }}</strong></p>
                        {{-- @if (Auth::user()->organization_id == '3578' || Auth::user()->organization_id == '3500')
                            <div class="marquee-container">
                                <p class="text-sm marquee-text">
                                    <strong>Nb: * Progres Provinsi dan Kota Surabaya sudah Terpisah. Progres sentra ekonomi sekarang
                                        juga hanya muncul sentra ekonomi yang menjadi tanggung jawab satker masing-masing.</strong>
                                </p>
                            </div>
                        @endif --}}
                    </div>
                    <div class="card-body">
                        <div style="width: 75%; margin: auto;">
                            <canvas id="proggress_chart"></canvas>
                        </div>
                        {{-- <div class="custom-note">
                            <strong>Note:</strong><br>
                            Sentra Ekonomi <code>target</code> dan <code>non target</code> adalah status sentra ekonomi yang ditentukan oleh
                            provinsi sesuai petunjuk dari kabupaten/kota.
                            Sentra Ekonomi <code>non target</code> adalah sentra ekonomi yang tidak dicacah karena berbagai alasan seperti
                            akses yang sulit, keamanan, bencana dll.
                            Pengajuan sentra ekonomi jenis ini bisa menghubungi <strong>Tim Garda Provinsi</strong>.<br><br>

                            Status penyelesaian sentra ekonomi ada 3 yaitu <code>Belum Dimulai</code>, <code>Sedang
                                Dikerjakan</code>, dan <code>Sudah Selesai</code>.
                            Untuk menandai sentra ekonomi yang sudah selesai, bisa menggunakan <strong>menu sentra ekonomi</strong> kemudian
                            <strong>ganti flag status penyelesaian</strong> pada tabel.

                        </div> --}}
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
                                @foreach ($reportByRegency as $report)
                                    <tr>
                                        <td>
                                            <div class="d-flex gap-3 align-items-center">
                                                <div>
                                                    <p class="text-xs text-secondary mb-0 mt-1">
                                                        [{{ $report->organization->long_code }}]
                                                    </p>
                                                    <h6 class="text-sm mb-1">{{ $report->organization->name }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center text-sm">{{ $report->total_market }}</td>

                                        <td class="align-middle text-center text-sm">{{ $report->target }}</td>
                                        <td class="align-middle text-center text-sm">{{ $report->non_target }}</td>
                                        <td class="align-middle text-center text-sm">{{ $report->not_start }}</td>
                                        <td class="align-middle text-center text-sm">{{ $report->on_going }}</td>
                                        <td class="align-middle text-center text-sm">{{ $report->done }}</td>

                                        <td class="align-middle text-center text-sm">{{ $report->market_have_business }}
                                        </td>
                                        <td class="align-middle text-center text-sm">{{ $report->uploaded }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-9 col-sm-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="text-capitalize">
                            Report Jumlah Usaha Berdasarkan Sentra Ekonomi
                        </h6>
                        <p class="text-sm">Kondisi tanggal: {{ $updateDate }} {{ $updateTime }}</p>
                    </div>
                    <div class="card-body">
                        <table id="marketTable" class="align-items-center text-sm">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-sm font-weight-bolder opacity-7">
                                        Nama Sentra Ekonomi
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
                                @foreach ($reportByMarket as $report)
                                    <tr>
                                        <td class="text-sm">{{ $report->market->name }}</td>
                                        <td class="text-sm">[{{ $report->market->village_id }}]
                                            {{ $report->market->subdistrict->name }} {{ $report->market->village->name }}
                                        </td>

                                        <td class="align-middle text-center text-sm">{{ $report->target_category }}</td>
                                        <td class="align-middle text-center text-sm">{{ $report->completion_status }}</td>

                                        <td class="align-middle text-center text-sm">{{ $report->uploaded }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-12 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="text-capitalize">
                            Report Jumlah Usaha Berdasarkan Petugas
                        </h6>
                        <p class="text-sm">Kondisi tanggal: {{ $updateDate }} {{ $updateTime }}</p>
                    </div>
                    <div class="card-body">
                        <table id="userTable" class="align-items-center text-sm">
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
                                @foreach ($reportByUser as $report)
                                    <tr>
                                        <td class="text-sm">{{ $report->user->firstname }}</td>
                                        <td class="align-middle text-center text-sm">{{ $report->uploaded }}</td>
                                    </tr>
                                @endforeach
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

    <script src="/vendor/chart.js/chart.js"></script>
    <script src="/vendor/chart.js/chartjs-plugin-datalabels@2.0.0"></script>

    <script src="/vendor/datatables/dataTables.min.js"></script>
    <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

    <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

    <script>
        let tableRegency = new DataTable('#regencyTable', {
            order: [],
            responsive: true,
            ordering: true,
            paging: false, // ❌ No pagination
            searching: false, // ❌ No search box
            ordering: true, // ✅ Columns still sortable
            info: false, // ❌ No "Showing X of Y" info
            pageLength: 38, // ✅ Show exactly 38 rows
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
                    responsivePriority: 3,
                    width: "5%",
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

        let tableUser = new DataTable('#userTable', {
            order: [],
            responsive: true,
            ordering: true,
            pageLength: 50,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                },
            ],
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            }
        });

        let marketUser = new DataTable('#marketTable', {
            order: [],
            responsive: true,
            ordering: true,
            pageLength: 50,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
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
                    responsivePriority: 2,
                    width: "10%",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            if (row[2] == 'target') {
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
                        label: 'Progres Pemutakhiran Sentra Ekonomi',
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
