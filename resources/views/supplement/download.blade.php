@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Download Project Suplemen'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h4 class="text-capitalize">Download Project Suplemen SW Maps</h4>
                </div>
                <p class="text-sm">Menu ini digunakan untuk mendownload project suplemen SW Maps berdasarkan wilayah</p>
            </div>
            <div class="card-body pt-1">
                <form id="formupdate" autocomplete="off" method="post" action="/suplemen/download" class="needs-validation"
                    enctype="multipart/form-data" novalidate>
                    @csrf
                    @if ($user->regency_id == null)
                        <div class="row">
                            <div class="col-md-4">
                                <label class="form-control-label">Kabupaten <span class="text-danger">*</span></label>
                                <select id="regency" name="regency" class="form-control" data-toggle="select">
                                    <option value="0" disabled selected> -- Pilih Kabupaten -- </option>
                                    @foreach ($regencies as $regency)
                                        <option value="{{ $regency->id }}"
                                            {{ old('regency') == $regency->id ? 'selected' : '' }}>
                                            [{{ $regency->short_code }}] {{ $regency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                            <select id="subdistrict" name="subdistrict" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Kecamatan -- </option>
                                @foreach ($subdistricts as $subdistrict)
                                    <option value="{{ $subdistrict->id }}"
                                        {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                        [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                            <select id="village" name="village" class="form-control" data-toggle="select"
                                name="village"></select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div id="update-error" style="display: none;">
                                <p class="error-feedback mb-0 mt-2">
                                    Desa Belum Dipilih
                                </p>
                            </div>
                        </div>
                    </div>
                    <button onclick="download()" class="btn btn-primary mt-3" id="submitBtn">Download</button>
                </form>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>

        <script>
            [{
                    selector: '#regency',
                    placeholder: 'Pilih Kabupaten'
                }, {
                    selector: '#subdistrict',
                    placeholder: 'Pilih Kecamatan'
                },
                {
                    selector: '#village',
                    placeholder: 'Pilih Desa'
                },
            ].forEach(config => {
                $(config.selector).select2({
                    placeholder: config.placeholder,
                    allowClear: true,
                });
            });

            const eventHandlers = {
                '#regency': () => {
                    loadSubdistrict('', null, null);
                },
                '#subdistrict': () => {
                    loadVillage('', null, null);
                },
                '#village': () => {

                },
            };

            Object.entries(eventHandlers).forEach(([selector, handler]) => {
                $(selector).on('change', handler);
            });

            function loadSubdistrict(group = '', regencyid = null, selectedvillage = null) {

                let regencySelector = `#regency${group}`;
                let subdistrictSelector = `#subdistrict${group}`;
                let villageSelector = `#village${group}`;
                let slsSelector = `#sls${group}`;

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

            function loadVillage(group = '', subdistrictid = null, selectedvillage = null) {

                let subdistrictSelector = `#subdistrict${group}`;
                let villageSelector = `#village${group}`;
                let slsSelector = `#sls${group}`;

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

            function validate() {
                let village = $('#village').val();

                if (village == null) {
                    $('#update-error').show();
                    return false;
                } else {
                    $('#update-error').hide();
                    return true;
                }
            }

            function download() {
                event.preventDefault();

                if (validate()) {
                    document.getElementById('formupdate').submit();

                }
            }
        </script>
    @endpush
@endsection
