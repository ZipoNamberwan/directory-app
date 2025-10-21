@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Pemeriksaan Duplikat'])
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
                    <div class="d-flex align-items-center">
                        <h5 class="text-capitalize mb-0">Pemeriksaan Usaha yang Duplikat</h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="row mb-3">
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
                            <label class="form-control-label">Status Matching Wilayah</label>
                            <select id="status" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Status -- </option>
                                <option value="all">Semua</option>
                                <option value="notconfirmed">Belum Dikonfirmasi</option>
                                <option value="deleteone">Salah satu usaha dihapus</option>
                                <option value="keepall">Tidak ada usaha yang dihapus</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-control-label" for="keyword">Cari</label>
                            <input type="text" name="keyword" class="form-control" id="keyword"
                                placeholder="Cari By Keyword">
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-control-label">Kabupaten</label>
                            <select style="width: 100%;" id="regency" name="regency" class="form-control"
                                data-toggle="select">
                                <option value="0" disabled selected> -- Filter Kabupaten -- </option>
                                @foreach ($regencies as $regency)
                                    <option value="{{ $regency->id }}"
                                        {{ old('regency') == $regency->id ? 'selected' : '' }}>
                                        [{{ $regency->short_code }}] {{ $regency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-control-label">Kecamatan</label>
                            <select style="width: 100%;" id="subdistrict" name="subdistrict" class="form-control"
                                data-toggle="select">
                                <option value="0" disabled selected> -- Filter Kecamatan -- </option>
                                @foreach ($subdistricts as $subdistrict)
                                    <option value="{{ $subdistrict->id }}"
                                        {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                        [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-control-label">Desa</label>
                            <select id="village" name="village" class="form-control" data-toggle="select"
                                name="village"></select>
                        </div>
                        <div id="sls_div" class="col-md-3">
                            <label class="form-control-label">SLS</label>
                            <select id="sls" name="sls" class="form-control" data-toggle="select"></select>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label d-block">Mode Tampilan Tabel:</label>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mode" id="fitColumns"
                                value="fit" checked>
                            <label class="form-check-label" for="fit">Muat Semua Kolom</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mode" id="fitDataTable"
                                value="responsive">
                            <label class="form-check-label" for="responsive">Responsif</label>
                        </div>

                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="mode" id="fitDataTable"
                                value="scroll">
                            <label class="form-check-label" for="scroll">Scroll Horizontal</label>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <p class="mb-2 text-muted small">
                            Jumlah kandidat duplikat: <span id="total-records" class="fw-bold">0</span>
                        </p>
                    </div>
                </div>
                <div id="data-table"></div>
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

    <script>
        const selectConfigs = [{
                selector: '#organization',
                placeholder: 'Pilih Satker'
            },
            {
                selector: '#status',
                placeholder: 'Pilih Status'
            },
            {
                selector: '#regency',
                placeholder: 'Pilih Kabupaten'
            },
            {
                selector: '#subdistrict',
                placeholder: 'Pilih Kecamatan'
            },
            {
                selector: '#village',
                placeholder: 'Pilih Desa'
            },
            {
                selector: '#sls',
                placeholder: 'Pilih SLS'
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
            '#status': () => {
                renderTable()
            },
            '#regency': () => {
                loadSubdistrict(null, null);
                renderTable()
            },
            '#subdistrict': () => {
                loadVillage(null, null);
                renderTable()
            },
            '#village': () => {
                loadSls(null, null);
                renderTable()
            },
            '#sls': () => {
                renderTable()
            },
        };


        function loadSubdistrict(regencyid = null, selectedvillage = null) {

            let regencySelector = `#regency`;
            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

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

        function loadVillage(subdistrictid = null, selectedvillage = null) {

            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

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

        function loadSls(villageid = null, selectedsls = null) {

            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(villageSelector).val();
            if (villageid != null) {
                id = villageid;
            }

            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/sls/' + id,
                    success: function(response) {
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                        response.forEach(element => {
                            let selected = selectedsls == String(element.id) ? 'selected' : '';
                            $(slsSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

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
            filterTypes = ['organization', 'status',
                'regency', 'subdistrict',
                'village', 'sls', 'keyword'
            ];
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            table.setData('/duplikat/data?' + filterUrl);
        }
    </script>

    <script>
        // Global table variable
        let table;

        // Define column configurations for different modes
        const getColumnConfig = (mode) => {
            const baseColumns = [{
                    title: "Usaha 1",
                    field: "center_business_name",
                    responsive: 0,
                    formatter: function(cell, formatterParams, onRendered) {
                        const data = cell.getRow().getData();
                        const name = data.center_business_name || '';
                        const owner = data.center_business_owner || '';
                        const type = data.center_business_type || '';

                        // Extract business type name from full class path
                        const businessType = type.includes('SupplementBusiness') ? 'Suplemen' :
                            type.includes('MarketBusiness') ? 'Sentra Ekonomi' :
                            type.replace('App\\Models\\', '');

                        return `<div>
                                    <div class="fw-bold">${name}</div>
                                    <div class="text-muted small">Pemilik: ${owner || '-'}</div>
                                    <div class="text-muted small">${businessType}</div>
                                </div>`;
                    }
                },
                {
                    title: "Usaha 2",
                    field: "nearby_business_name",
                    responsive: 1,
                        formatter: function(cell, formatterParams, onRendered) {
                        const data = cell.getRow().getData();
                        const name = data.nearby_business_name || '';
                        const owner = data.nearby_business_owner || '';
                        const type = data.nearby_business_type || '';

                        // Extract business type name from full class path
                        const businessType = type.includes('SupplementBusiness') ? 'Suplemen' :
                            type.includes('MarketBusiness') ? 'Sentra Ekonomi' :
                            type.replace('App\\Models\\', '');

                        return `<div>
                                    <div class="fw-bold">${name}</div>
                                    <div class="text-muted small">Pemilik: ${owner || '-'}</div>
                                    <div class="text-muted small">${businessType}</div>
                                </div>`;
                    }
                },
                {
                    title: "Kemiripan",
                    field: "confidence_score",
                    responsive: 2,
                    headerHozAlign: "center",
                    formatter: function(cell, formatterParams, onRendered) {
                        const data = cell.getRow().getData();
                        const nameScore = (data.name_similarity * 100).toFixed(1);
                        const ownerScore = (data.owner_similarity * 100).toFixed(1);
                        const overallScore = (data.confidence_score * 100).toFixed(1);

                        // Determine color based on score
                        let scoreColor = 'success';
                        let bgColor = 'rgba(25, 135, 84, 0.1)';
                        if (overallScore < 75) {
                            scoreColor = 'danger';
                            bgColor = 'rgba(220, 53, 69, 0.1)';
                        } else if (overallScore < 90) {
                            scoreColor = 'warning';
                            bgColor = 'rgba(255, 193, 7, 0.1)';
                        }

                        return `<div>
                            <div class="mb-2 rounded text-center" style="background: ${bgColor};">
                                <div class="fw-bold text-${scoreColor}">${overallScore}%</div>
                                <small class="text-muted">Keseluruhan</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1 px-1">
                                <small class="text-muted">Nama:</small>
                                <small class="fw-semibold">${nameScore}%</small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center px-1">
                                <small class="text-muted">Pemilik:</small>
                                <small class="fw-semibold">${ownerScore}%</small>
                            </div>
                        </div>`;
                    }
                },
                {
                    title: "Jarak",
                    field: "distance_meters",
                    responsive: 3,
                    headerHozAlign: "center",
                    hozAlign: "center",
                    formatter: function(cell, formatterParams, onRendered) {
                        const distance = cell.getValue();
                        if (distance === null || distance === undefined) {
                            return '<span class="text-muted">-</span>';
                        }
                        return distance < 1000 ?
                            `${Math.round(distance)}m` :
                            `${(distance / 1000).toFixed(1)}km`;
                    }
                },
                {
                    title: "Aksi",
                    field: "id",
                    responsive: 7,
                    headerHozAlign: "center",
                    hozAlign: "center",
                }
            ];

            // Apply mode-specific configurations
            if (mode === "fit") {
                // No width/minWidth, no responsive collapse column
                return baseColumns;
            } else if (mode === "responsive") {
                // Add responsive collapse column at the beginning
                const responsiveColumn = {
                    formatter: "responsiveCollapse",
                    width: 30,
                    hozAlign: "center",
                    resizable: false,
                    headerSort: false
                };

                // Set widths for responsive mode
                baseColumns[0].widthGrow = 2;
                baseColumns[0].minWidth = 150;
                baseColumns[1].widthGrow = 2;
                baseColumns[1].minWidth = 150;
                baseColumns[3].width = 100;
                baseColumns[3].minWidth = 80;
                baseColumns[4].width = 100;
                baseColumns[4].minWidth = 80;

                return [responsiveColumn, ...baseColumns];
            } else { // scroll horizontal
                // Set widths for horizontal scroll mode
                baseColumns[0].widthGrow = 2;
                baseColumns[0].minWidth = 150;
                baseColumns[1].widthGrow = 2;
                baseColumns[1].minWidth = 150;
                baseColumns[3].width = 100;
                baseColumns[3].minWidth = 80;
                baseColumns[4].width = 100;
                baseColumns[4].minWidth = 80;

                return baseColumns;
            }
        };

        // Get table configuration based on mode
        const getTableConfig = (mode) => {
            const baseConfig = {
                height: "800px",
                layout: "fitColumns",
                ajaxURL: "/duplikat/data",
                progressiveLoad: "scroll",
                paginationSize: 20,
                placeholder: "Tidak ada usaha yang ditemukan",
                textDirection: "auto",
                ajaxResponse: function(url, params, response) {
                    document.getElementById("total-records").textContent = response.total_records;
                    return response;
                },
                columns: getColumnConfig(mode)
            };

            if (mode === "responsive") {
                baseConfig.responsiveLayout = "collapse";
                baseConfig.responsiveLayoutCollapseStartOpen = false;
            }

            return baseConfig;
        };

        // Initialize table with default mode
        const initializeTable = (mode = "fit") => {
            table = new Tabulator("#data-table", getTableConfig(mode));
        };

        // Recreate table with new mode
        const recreateTable = (mode) => {
            if (table) {
                table.destroy();
            }
            initializeTable(mode);
        };

        // Reset all Select2 filters without triggering change events
        const resetSelect2Filters = () => {
            selectConfigs.forEach(({
                selector
            }) => {
                // Reset value without triggering change event
                $(selector).val(null);
                // Update the Select2 display without triggering change
                $(selector).trigger('change.select2');
            });

            document.getElementById('keyword').value = '';
        };

        // Event listener for mode changes
        document.querySelectorAll('input[name="mode"]').forEach(radio => {
            radio.addEventListener("change", function(e) {
                let mode = e.target.value;

                // Reset all Select2 filters
                resetSelect2Filters();

                // Recreate table with new mode configuration
                recreateTable(mode);
            });
        });

        // Initialize table on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Get initial mode from checked radio button
            const checkedRadio = document.querySelector('input[name="mode"]:checked');
            const initialMode = checkedRadio ? checkedRadio.value : "fit";

            initializeTable(initialMode);
        });
    </script>
@endpush
