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
    <!-- <div class="row">
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-2">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-2 text-uppercase font-weight-bold">Total Prelist Usaha</p>
                                <h5 class="font-weight-bolder">
                                    {{$total}}
                                </h5>
                                <p class="mb-0">
                                    <span class="text-sm">Total direktori usaha yang harus dimutakhirkan</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-secondary shadow-secondary text-center rounded-circle">
                                <i class="ni ni-money-coins text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-2">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Ada</p>
                                <h5 class="font-weight-bolder">
                                    {{$active}}
                                </h5>
                                <p class="mb-0">
                                    <span class="text-sm">Jumlah direktori usaha dengan status Ada</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-success shadow-success text-center rounded-circle">
                                <i class="ni ni-world text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-2">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Tidak Ada</p>
                                <h5 class="font-weight-bolder">
                                    {{$not_active}}
                                </h5>
                                <p class="mb-0">
                                    <span class="text-sm">Jumlah direktori usaha dengan status Tidak Ada</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-danger shadow-danger text-center rounded-circle">
                                <i class="ni ni-paper-diploma text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-2">
            <div class="card">
                <div class="card-body p-3">
                    <div class="row">
                        <div class="col-8">
                            <div class="numbers">
                                <p class="text-sm mb-0 text-uppercase font-weight-bold">Baru</p>
                                <h5 class="font-weight-bolder">
                                    {{$new}}
                                </h5>
                                <p class="mb-0">
                                    <span class="text-sm">Jumlah direktori usaha Baru</span>
                                </p>
                            </div>
                        </div>
                        <div class="col-4 text-end">
                            <div class="icon icon-shape bg-gradient-info shadow-info text-center rounded-circle">
                                <i class="ni ni-cart text-lg opacity-10" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

    <div class="row g-4">
        <!-- SLS Section -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Direktori Sampai Level SLS</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="card status-card card-active">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Ada</h6>
                                            <h3 class="mb-0" id="sls-active">0</h3>
                                        </div>
                                        <i class="fas fa-check-circle text-success fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card status-card card-not-active">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Tidak Ada</h6>
                                            <h3 class="mb-0" id="sls-not-active">0</h3>
                                        </div>
                                        <i class="fas fa-times-circle text-danger fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card status-card card-new">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Baru</h6>
                                            <h3 class="mb-0" id="sls-new">0</h3>
                                        </div>
                                        <i class="fas fa-star text-primary fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card status-card card-not-included">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Bukan Cakupan SE</h6>
                                            <h3 class="mb-0" id="sls-not-included">0</h3>
                                        </div>
                                        <i class="fas fa-ban text-warning fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Non-SLS Section -->
        <div class="col-12 col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Directory Tidak Sampai Level SLS</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="card status-card card-active">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Ada</h6>
                                            <h3 class="mb-0" id="non-sls-active">0</h3>
                                        </div>
                                        <i class="fas fa-check-circle text-success fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card status-card card-not-active">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Tidak Ada</h6>
                                            <h3 class="mb-0" id="non-sls-not-active">0</h3>
                                        </div>
                                        <i class="fas fa-times-circle text-danger fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card status-card card-new">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Baru</h6>
                                            <h3 class="mb-0" id="non-sls-new">0</h3>
                                        </div>
                                        <i class="fas fa-star text-primary fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="card status-card card-not-included">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="text-muted mb-2">Bukan Cakupan SE</h6>
                                            <h3 class="mb-0" id="non-sls-not-included">0</h3>
                                        </div>
                                        <i class="fas fa-ban text-warning fs-3"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Section -->
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">Total Businesses</h5>
                    <h2 id="total-businesses">0</h2>
                </div>
            </div>
        </div>


        <div class="row mt-3">
            <div class="col-lg-12 mb-lg-0 mb-4">
                <div class="card z-index-2 h-100">
                    <div class="card-header pb-0 pt-3 bg-transparent">
                        <h6 class="text-capitalize">Assignment Direktori Usaha</h6>
                        <p class="text-sm mb-0">
                            <span>Berikut rekap semua assignment direktori usaha</span>
                        </p>
                    </div>
                    <div class="card-body p-3">
                        <div class="row mb-2">
                            <div class="col-sm-12 col-md-3 my-1">
                                <select id="status" name="status" class="form-control" data-toggle="select" required>
                                    <option value="0" disabled selected> -- Filter Status -- </option>
                                    <option value="all"> Semua </option>
                                    @foreach($statuses as $status)
                                    <option value="{{$status->id}}">{{$status->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-12 col-md-3 my-1">
                                <select style="width: 100%;" id="subdistrict" name="subdistrict" class="form-control" data-toggle="select">
                                    <option value="0" disabled selected> -- Filter Kecamatan -- </option>
                                    @foreach ($subdistricts as $subdistrict)
                                    <option value="{{ $subdistrict->id }}" {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                        [{{ $subdistrict->short_code}}] {{ $subdistrict->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-sm-12 col-md-3 my-1">
                                <select id="village" name="village" class="form-control" data-toggle="select" name="village"></select>
                            </div>
                            <div id="sls_div" class="col-sm-12 col-md-3 my-1">
                                <select id="sls" name="sls" class="form-control" data-toggle="select"></select>
                            </div>
                            <div class="col-sm-12 col-md-3 my-1">
                                <select id="assignment" name="assignment" class="form-control" data-toggle="select" required>
                                    <option value="0" disabled selected> -- Filter Assignment -- </option>
                                    <option value="all"> Semua </option>
                                    <option value="1">Sudah Diassign</option>
                                    <option value="0">Belum Diassign</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <table id="myTable" class="align-items-center mb-0 text-sm">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Usaha</th>
                                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Wilayah</th>
                                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Status</th>
                                        <th class="text-uppercase text-small font-weight-bolder opacity-7">PCL</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
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
    <script src="/vendor/datatables/dataTables.min.js"></script>
    <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

    <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

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
                        $('#village').append(`<option value="0" disabled selected> -- Filter Desa -- </option>`);
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
                            return '<p class="mb-0"><span class="badge bg-gradient-' + data.color + '">' + data.name + '</span></p>';
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
        // Sample data - replace with your actual data
        const data = {
            sls: {
                active: 150,
                notActive: 45,
                new: 30,
                notIncluded: 25
            },
            nonSls: {
                active: 200,
                notActive: 60,
                new: 40,
                notIncluded: 35
            }
        };

        // Function to update the dashboard
        function updateDashboard(data) {
            // Update SLS numbers
            document.getElementById('sls-active').textContent = data.sls.active;
            document.getElementById('sls-not-active').textContent = data.sls.notActive;
            document.getElementById('sls-new').textContent = data.sls.new;
            document.getElementById('sls-not-included').textContent = data.sls.notIncluded;

            // Update Non-SLS numbers
            document.getElementById('non-sls-active').textContent = data.nonSls.active;
            document.getElementById('non-sls-not-active').textContent = data.nonSls.notActive;
            document.getElementById('non-sls-new').textContent = data.nonSls.new;
            document.getElementById('non-sls-not-included').textContent = data.nonSls.notIncluded;

            // Calculate and update total
            const total = Object.values(data.sls).reduce((a, b) => a + b, 0) +
                Object.values(data.nonSls).reduce((a, b) => a + b, 0);
            document.getElementById('total-businesses').textContent = total;
        }

        // Initialize dashboard with sample data
        updateDashboard(data);
    </script>
    @endpush