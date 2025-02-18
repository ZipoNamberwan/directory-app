@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
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
                                        <p class="text-sm mb-2 text-uppercase font-weight-bold">
                                            @if ($type == 'sls')
                                                Progress Pemutakhiran Direktori Sampai Level SLS
                                            @else
                                                Progress Pemutakhiran Direktori Tidak Sampai Level SLS
                                            @endif
                                        </p>
                                        <h5 class="font-weight-bolder">
                                            {{ $cardData[$type]['percentage'] }}%
                                        </h5>
                                        <p class="mb-0">
                                            <span class="text-sm"><strong
                                                    class="text-{{ $type == 'sls' ? 'info' : 'success' }}">{{ $cardData[$type]['updated'] }}/{{ $cardData[$type]['total'] }}</strong>
                                                sudah dimutakhirkan.</span>
                                        </p>
                                        <p class="text-xs text-secondary mb-0">
                                            Kondisi tanggal {{ $lastUpdateFormatted }}
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
                                                    <a target="_blank"
                                                        href="/report/{{ $lastUpdate }}/{{ $type }}/kec/{{ $reg->regency->long_code }}">
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
    </div>
@endsection

@push('js')
    <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>

    <script src="/vendor/chart.js/chart.js"></script>
    <script src="/vendor/chart.js/chartjs-plugin-datalabels@2.0.0"></script>

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
