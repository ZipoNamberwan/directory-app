@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => $title])
    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <h6 class="text-capitalize">
                    {{ $title }}
                </h6>
                <p class="text-sm text-muted"><span>Kondisi tanggal: {{ $dateFormatted }}</span></p>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table align-items-center">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                                    Identitas Wilayah
                                </th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                    Sudah Diupdate
                                </th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                    Total Direktori
                                </th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 text-center">
                                    Progres Pencacahan
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $dt)
                                <tr>
                                    <td>
                                        @if ($level == 'kec')
                                            <a target="_blank"
                                                href="/report/{{ $date }}/{{ $type }}/des/{{ $dt->long_code }}">
                                                <div class="d-flex gap-3 align-items-center">
                                                    <i class="fas fa-square-arrow-up-right text-lg opacity-10"
                                                        aria-hidden="true">
                                                    </i>
                                                    <div>
                                                        <p class="text-xs text-secondary mb-0">
                                                            [{{ $dt->long_code }}]
                                                        </p>
                                                        <h6 class="mb-0 text-sm">{{ $dt->name }}</h6>
                                                    </div>
                                                </div>
                                            </a>
                                        @else
                                            <div>
                                                <p class="text-xs text-secondary mb-0">
                                                    [{{ $dt->long_code }}]
                                                </p>
                                                <h6 class="mb-0 text-sm">{{ $dt->name }}</h6>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <p class="text-xs text-secondary mb-0">
                                            {{ $dt->updated }}
                                        </p>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <p class="text-xs text-secondary mb-0">
                                            {{ $dt->total }}
                                        </p>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <h6 class="mb-0 text-sm">{{ $dt->percentage }}</h6>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @include('layouts.footers.auth.footer')
    </div>
@endsection

@push('js')
@endpush
