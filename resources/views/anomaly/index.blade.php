@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                        <label class="form-control-label" for="keyword">Cari</label>
                        <input type="text" name="keyword" class="form-control" id="keyword"
                            placeholder="Cari By Keyword">
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
                selector: '#businessType',
                placeholder: 'Pilih Jenis Usaha'
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
            '#businessType': () => {
                renderTable()
            },
        };

        Object.entries(eventHandlers).forEach(([selector, handler]) => {
            $(selector).on('change', handler);
        });

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
            filterTypes = ['organization', 'anomalyType', 'businessType', 'keyword'];
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            console.log(filterUrl)

            // table.setData('/suplemen/data?' + filterUrl);
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
@endpush
