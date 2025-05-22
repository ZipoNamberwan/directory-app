@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Tambah Sentra Ekonomi'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h4 class="text-capitalize">{{ $market == null ? 'Tambah Sentra Ekonomi' : 'Edit Sentra Ekonomi' }}</h4>
                </div>
            </div>
            <div class="card-body pt-1">
                <form id="formupdate" autocomplete="off" method="post"
                    action="{{ $market == null ? '/pasar/manajemen' : '/pasar/manajemen/' . $market->id }}"
                    class="needs-validation" enctype="multipart/form-data" novalidate>
                    @csrf

                    @if ($market != null)
                        @method('PUT')
                    @else
                        @method('POST')
                    @endif

                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label">Kabupaten <span class="text-danger">*</span></label>
                            <select id="regency" name="regency" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Kabupaten -- </option>
                                @foreach ($regencies as $regency)
                                    <option value="{{ $regency->id }}"
                                        {{ old('regency', $market != null ? $market->regency_id : null) == $regency->id ? 'selected' : '' }}>
                                        [{{ $regency->short_code }}] {{ $regency->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('regency')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                            <select id="subdistrict" name="subdistrict" class="form-control" data-toggle="select"
                                name="subdistrict"></select>
                            @error('subdistrict')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                            <select id="village" name="village" class="form-control" data-toggle="select"
                                name="village"></select>
                            @error('village')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mt-3">
                            <label class="form-control-label" for="name">Nama <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                value="{{ @old('name', $market != null ? $market->name : '') }}" id="name"
                                placeholder="Nama Sentra Ekonomi">
                            @error('name')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mt-3">
                            <label class="form-control-label" for="address">Alamat (Opsional)</label>
                            <input type="text" name="address" class="form-control @error('address') is-invalid @enderror"
                                value="{{ @old('address', $market != null ? $market->address : '') }}" id="address"
                                placeholder="Alamat">
                            @error('address')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label">Tipe <span class="text-danger">*</span></label>
                            <select id="marketType" name="marketType" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Tipe -- </option>
                                @foreach ($marketTypes as $marketType)
                                    <option value="{{ $marketType->id }}"
                                        {{ old('marketType', $market != null ? $market->market_type_id : null) == $marketType->id ? 'selected' : '' }}>
                                        {{ $marketType->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('marketType')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    @hasrole('adminprov')
                    <div class="row">
                        <div class="col-md-4 mt-3">
                            <label class="form-control-label" for="address">Apakah Dikerjakan Provinsi?</label>
                            <input type="hidden" name="managedbyprov" value="0">
                            <div class="form-check form-switch">
                                <input value="1" onchange="toggleLabel()" class="form-check-input" name="managedbyprov"
                                    type="checkbox" id="managedbyprov"
                                    {{ old('managedbyprov', ($market->organization_id ?? 0) == 3500) ? 'checked' : '' }}>
                                <label id="switchlabel" class="form-check-label" for="managedbyprov">Tidak</label>
                            </div>
                        </div>
                    </div>
                    @endhasrole

                    <button class="btn btn-primary mt-3" id="submit" type="submit">Submit</button>
                </form>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>

        <script>
            function toggleLabel() {
                const checkbox = document.getElementById('managedbyprov');
                const label = document.getElementById('switchlabel');

                if (checkbox.checked) {
                    label.textContent = 'Ya';
                } else {
                    label.textContent = 'Tidak';
                }
            }
        </script>

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
                {
                    selector: '#marketType',
                    placeholder: 'Pilih Tipe'
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

            function loadSubdistrict(group = '', regencyid = null, selectedSubdistrict = null) {
                return new Promise((resolve) => {
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
                                    `<option value="0" disabled selected> -- Pilih Kecamatan -- </option>`
                                );
                                $(villageSelector).empty().append(
                                    `<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                                $(slsSelector).empty().append(
                                    `<option value="0" disabled selected> -- Pilih SLS -- </option>`);

                                response.forEach(element => {
                                    let selected = selectedSubdistrict == String(element.id) ?
                                        'selected' : '';
                                    $(subdistrictSelector).append(
                                        `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                                    );
                                });

                                resolve(); // ✅ Resolve when done
                            },
                            error: function() {
                                resolve(); // still resolve to not block next steps
                            }
                        });
                    } else {
                        $(subdistrictSelector).empty().append(
                            `<option value="0" disabled> -- Pilih Kecamatan -- </option>`);
                        $(villageSelector).empty().append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
                        $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
                        resolve();
                    }
                });
            }

            function loadVillage(group = '', subdistrictid = null, selectedVillage = null) {
                return new Promise((resolve) => {
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
                                    let selected = selectedVillage == String(element.id) ?
                                        'selected' : '';
                                    $(villageSelector).append(
                                        `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                                    );
                                });

                                resolve();
                            },
                            error: function() {
                                resolve();
                            }
                        });
                    } else {
                        $(villageSelector).empty().append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
                        $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
                        resolve();
                    }
                });
            }

            (async () => {
                const regencyToLoad = {{ Js::from(old('regency', $market?->regency_id)) }};
                const subdistrictToLoad = {{ Js::from(old('subdistrict', $market?->subdistrict_id)) }};
                const villageToLoad = {{ Js::from(old('village', $market?->village_id)) }};

                if (regencyToLoad) {
                    await loadSubdistrict('', regencyToLoad, subdistrictToLoad);

                    if (subdistrictToLoad) {
                        await loadVillage('', subdistrictToLoad, villageToLoad);
                    }
                }
            })();
        </script>
    @endpush
@endsection
