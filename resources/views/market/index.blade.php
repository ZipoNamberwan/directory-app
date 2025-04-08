@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
<link href="/assets/css/app.css" rel="stylesheet" />
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
<link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
@include('layouts.navbars.auth.topnav', ['title' => 'Daftar Direktori Usaha Pasar'])
<div class="container-fluid py-4">

    <div class="card mt-2">
        <div class="card-header pb-0">
            <div class="d-flex align-items-center">
                <h6 class="text-capitalize">Daftar Usaha</h6>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                @hasrole('adminprov')
                <div class="col-md-3">
                    <label class="form-control-label">Kabupaten <span class="text-danger">*</span></label>
                    <select id="regency" name="regency" class="form-control" data-toggle="select">
                        <option value="0" disabled selected> -- Pilih Kabupaten -- </option>
                        @foreach ($regencies as $regency)
                        <option value="{{ $regency->id }}" {{ old('regency') == $regency->id ? 'selected' : '' }}>
                            [{{ $regency->short_code }}] {{ $regency->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endhasrole
                <div class="col-md-3">
                    <label class="form-control-label">Pasar <span class="text-danger">*</span></label>
                    <select id="market" name="market" class="form-control" data-toggle="select">
                        @foreach ($markets as $market)
                        <option value="{{ $market->id }}" {{ old('market') == $market->id ? 'selected' : '' }}>
                            {{ $market->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <table id="myTable" class="align-items-center mb-0 text-sm">
                <thead>
                    <tr>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Usaha</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Pemilik</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Catatan</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Pasar</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Kabupaten</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Created At</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
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

<script>
    const selectConfigs = [{
            selector: '#regency',
            placeholder: 'Pilih Kabupaten'
        },
        {
            selector: '#market',
            placeholder: 'Pilih Pasar'
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
        '#regency': () => {
            loadMarket(null, null)
            renderTable()
        },
        '#market': () => {
            renderTable()
        },
    };

    Object.entries(eventHandlers).forEach(([selector, handler]) => {
        $(selector).on('change', handler);
    });

    function loadMarket(regencyid = null, selectedmarket = null) {

        let regencySelector = `#regency`;
        let marketSelector = `#market`;

        let id = $(regencySelector).val();
        if (regencyid != null) {
            id = regencyid;
        }

        $(marketSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

        if (id != null) {
            $.ajax({
                type: 'GET',
                url: '/pasar/kab/' + id,
                success: function(response) {
                    $(marketSelector).empty().append(
                        `<option value="0" disabled selected> -- Pilih Pasar -- </option>`);
                    response.forEach(element => {
                        let selected = selectedmarket == String(element.id) ? 'selected' : '';
                        $(marketSelector).append(
                            `<option value="${element.id}" ${selected}>${element.name}</option>`
                        );
                    });
                }
            });
        } else {
            $(marketSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
        }
    }

    function getFilterUrl(filter) {
        var filterUrl = ''
        var e = document.getElementById(filter);
        if (e != null) {
            var filterselected = e.options[e.selectedIndex];
            if (filterselected != null) {
                var filterid = filterselected.value
                if (filterid != 0) {
                    filterUrl = `&${filter}=` + filterid
                }
            }
        }

        return filterUrl
    }

    function renderTable() {
        filterUrl = ''
        filterTypes = ['regency', 'market']
        filterTypes.forEach(f => {
            filterUrl += getFilterUrl(f)
        });

        table.ajax.url('/pasar/data?' + filterUrl).load();
    }

    function formatDate(isoString) {
        const date = new Date(isoString);

        let formatted = new Intl.DateTimeFormat('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        }).format(date);
        return formatted.replace(" pukul ", " ").replace(/\./g, ":");
    }

    let table = new DataTable('#myTable', {
        order: [],
        serverSide: true,
        processing: true,
        // deferLoading: 0,
        ajax: {
            url: '/pasar/data',
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
                data: "owner",
                type: "text",
            },
            {
                responsivePriority: 3,
                width: "10%",
                data: "note",
                type: "text",
            },
            {
                responsivePriority: 4,
                width: "10%",
                data: "market",
                type: "text",
                render: function(data, type, row) {
                    return data.name
                }
            },
            {
                responsivePriority: 4,
                width: "10%",
                data: "regency",
                type: "text",
                render: function(data, type, row) {
                    return data.name;
                }
            },
            {
                responsivePriority: 4,
                width: "10%",
                data: "created_at",
                type: "text",
                render: function(data, type, row) {
                    return formatDate(data)
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
@endpush