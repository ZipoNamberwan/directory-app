@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Tambah Assignment'])
    <div class="container-fluid py-4">

        <div class="card z-index-2 h-100">
            <div class="card-header pb-0 pt-3 bg-transparent">
                <h6 class="text-capitalize">Tambah Assignment</h6>
                <p class="text-sm mb-0">
                    <span>Tambah Assignment Secara Manual</span>
                </p>
            </div>
            <div class="card-body p-3">
                <form autocomplete="off" method="post" action="/pasar-assignment/store" class="needs-validation"
                    enctype="multipart/form-data" novalidate>
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label">Pasar <span class="text-danger">*</span></label>
                            <select id="market" name="market" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Pasar -- </option>
                                @foreach ($markets as $market)
                                    <option value="{{ $market->id }}"
                                        {{ old('market') == $market->id ? 'selected' : '' }}>
                                        {{ $market->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('market')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label">User <span class="text-danger">*</span></label>
                            <select id="user" name="user" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih User -- </option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user') == $user->id ? 'selected' : '' }}>
                                        {{ $user->firstname }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" id="submit" type="submit">Tambah</button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="/vendor/select2/select2.min.js"></script>
    <script src="/vendor/datatables/dataTables.min.js"></script>
    <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

    <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <script src="/vendor/datatables/dataTables.responsive.min.js"></script>
    <script src="/vendor/sweetalert2/sweetalert2.js"></script>

    <script>
        const selectConfigs = [{
                selector: '#market',
                placeholder: 'Pilih Pasar'
            },
            {
                selector: '#user',
                placeholder: 'Pilih User'
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
    </script>
@endpush
