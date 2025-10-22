@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
          integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
          crossorigin=""/>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Fix z-index issue - Modal should be above sidenav */
        .modal {
            z-index: 1055 !important;
        }

        .modal-backdrop {
            z-index: 1050 !important;
        }

        /* Ensure sidenav stays behind modal */
        .sidenav {
            z-index: 1030 !important;
        }
    </style>
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
                            <label class="form-control-label">Status Pemeriksaan</label>
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

    <!-- Duplicate Detail Modal -->
    <div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header text-dark">
                    <h5 class="modal-title" id="duplicateModalLabel">
                        <i class="fas fa-balance-scale me-2"></i>Confirm Duplicate Businesses
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Loading State -->
                    <div id="map-loading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2 text-muted">Memuat peta dan detail usaha...</p>
                    </div>

                    <!-- Content Container -->
                    <div id="content-container" style="display: none;">
                        <!-- Map Section - Top -->
                        <div class="position-relative">
                            <div id="business-map" style="height: 400px; width: 100%;"></div>
                            
                            <!-- Similarity Info Overlay - Bottom Center -->
                            <div class="position-absolute" style="bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1000;">
                                <div class="card border-secondary shadow-sm" style="min-width: 400px;">
                                    <div class="card-body py-2 bg-white text-dark">
                                        <div id="similarity-content">
                                            <!-- Content will be populated here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Business Comparison - Bottom -->
                        <div class="p-4">
                            <div class="row">
                                <!-- Business A -->
                                <div class="col-md-6">
                                    <div class="card border-info shadow-sm h-100">
                                        <div class="card-header bg-info text-dark py-2">
                                            <h6 class="mb-0"><i class="fas fa-building"></i> Usaha A</h6>
                                        </div>
                                        <div class="card-body py-2">
                                            <div id="center-business-content">
                                                <!-- Content will be populated here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Business B -->
                                <div class="col-md-6">
                                    <div class="card border-success shadow-sm h-100">
                                        <div class="card-header bg-success text-white py-2">
                                            <h6 class="mb-0"><i class="fas fa-building"></i> Usaha B</h6>
                                        </div>
                                        <div class="card-body py-2">
                                            <div id="nearby-business-content">
                                                <!-- Content will be populated here -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-danger me-2" id="delete-business-a">
                        <i class="fas fa-trash-alt me-1"></i>Hapus Usaha A
                    </button>
                    <button type="button" class="btn btn-danger me-2" id="delete-business-b">
                        <i class="fas fa-trash-alt me-1"></i>Hapus Usaha B
                    </button>
                    <button type="button" class="btn btn-success me-2" id="keep-both">
                        <i class="fas fa-check-circle me-1"></i>Keep Both
                    </button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('js')
    <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="/vendor/select2/select2.min.js"></script>
    <script src="/vendor/sweetalert2/sweetalert2.js"></script>

    <script src="/vendor/tabulator/tabulator.min.js"></script>
    
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""></script>

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
                    title: "Usaha A",
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
                    title: "Usaha B",
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
                    formatter: function(cell, formatterParams, onRendered) {
                        const id = cell.getValue();
                        return `<button class="btn btn-info btn-sm px-2 py-1" data-id="${id}">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>`;
                    },
                    cellClick: function(e, cell) {
                        const id = cell.getValue();
                        const rowData = cell.getRow().getData();
                        showDuplicateDialog(id, rowData);
                    }
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

        // Function to show duplicate detail dialog
        function showDuplicateDialog(id, rowData) {
            // Store current candidate ID for actions
            currentCandidateId = id;
            
            // Update modal title with ID
            document.getElementById('duplicateModalLabel').textContent = `Detail Kandidat Duplikat`;
            
            // Reset content to loading state
            document.getElementById('map-loading').style.display = 'block';
            document.getElementById('content-container').style.display = 'none';
            
            // Show the modal
            const modal = new bootstrap.Modal(document.getElementById('duplicateModal'));
            modal.show();
            
            // Fetch business details from API
            fetchBusinessDetails(id, rowData);
        }

        // Function to fetch business details from API
        function fetchBusinessDetails(candidateId, rowData) {
            fetch(`/duplikat/pair/${candidateId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    displayBusinessDetails(data, rowData);
                })
                .catch(error => {
                    console.error('Error fetching business details:', error);
                    // Show error in loading area
                    document.getElementById('map-loading').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            Gagal memuat detail usaha. Silakan coba lagi.
                        </div>`;
                });
        }

        // Global map variable
        let businessMap = null;

        // Function to display business details on map
        function displayBusinessDetails(data, rowData) {
            const centerBusiness = data.center_business;
            const nearbyBusiness = data.nearby_business;
            
            // Get similarity data from rowData (from the table)
            const similarities = {
                name_similarity: rowData.name_similarity || 0,
                owner_similarity: rowData.owner_similarity || 0,
                confidence_score: rowData.confidence_score || 0,
                duplicate_status: rowData.duplicate_status || 'not_duplicate',
                distance_meters: rowData.distance_meters || null
            };

            // Hide loading and show content container
            document.getElementById('map-loading').style.display = 'none';
            document.getElementById('content-container').style.display = 'block';

            // Wait a bit for the modal and map container to be fully rendered
            setTimeout(() => {
                // Initialize map if not already created
                if (!businessMap) {
                    businessMap = L.map('business-map');
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(businessMap);
                } else {
                    // Clear existing layers
                    businessMap.eachLayer(function(layer) {
                        if (layer instanceof L.Marker || layer instanceof L.Polyline) {
                            businessMap.removeLayer(layer);
                        }
                    });
                }

                // Force map to recalculate its size
                businessMap.invalidateSize();

                // Create markers for both businesses
                const centerLatLng = [parseFloat(centerBusiness.latitude), parseFloat(centerBusiness.longitude)];
                const nearbyLatLng = [parseFloat(nearbyBusiness.latitude), parseFloat(nearbyBusiness.longitude)];

                // Create custom icons with exact Bootstrap colors
                const centerIcon = L.divIcon({
                    className: 'custom-marker',
                    html: '<div style="background-color: #11cdef; border: 3px solid white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="fas fa-building text-white" style="font-size: 12px;"></i></div>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                const nearbyIcon = L.divIcon({
                    className: 'custom-marker',
                    html: '<div style="background-color: #2dce89; border: 3px solid white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="fas fa-building text-white" style="font-size: 12px;"></i></div>',
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                // Add markers
                const centerMarker = L.marker(centerLatLng, {icon: centerIcon}).addTo(businessMap);
                const nearbyMarker = L.marker(nearbyLatLng, {icon: nearbyIcon}).addTo(businessMap);

                // Add connecting line
                const line = L.polyline([centerLatLng, nearbyLatLng], {
                    color: similarities.duplicate_status === 'strong_duplicate' ? '#dc3545' : 
                           similarities.duplicate_status === 'weak_duplicate' ? '#ffc107' : '#6c757d',
                    weight: 3,
                    opacity: 0.7,
                    dashArray: similarities.duplicate_status === 'not_duplicate' ? '10, 10' : null
                }).addTo(businessMap);

                // Fit map to show both markers with maximum zoom level
                const group = new L.featureGroup([centerMarker, nearbyMarker]);
                
                // Set to maximum zoom to show closest detail
                const bounds = group.getBounds().pad(0.1);
                businessMap.fitBounds(bounds, {
                    padding: [10, 10] // Minimal padding for maximum zoom
                });
            }, 100);

            // Populate business detail cards
            populateBusinessCard('center', centerBusiness);
            populateBusinessCard('nearby', nearbyBusiness);
            populateSimilarityCard(similarities);
        }

        // Function to populate individual business card
        function populateBusinessCard(type, business) {
            // Format user information
            const userInfo = business.user ? 
                `${business.user.firstname || '-'} (${business.user.organization_id || '-'})` : 
                '-';

            // Format organization information    
            const orgInfo = business.organization ? 
                `${business.organization.name || '-'} (${business.organization.id || '-'})` : 
                '-';
            
            // Format created_at date and time
            const createdAt = business.created_at ? 
                new Date(business.created_at).toLocaleString('id-ID', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }) : '-';
            
            const content = `
                <h6 class="fw-bold text-${type === 'center' ? 'info' : 'success'} mb-2">${business.name || '-'}</h6>
                <p class="mb-1 small"><strong>Pemilik:</strong> ${business.owner || '-'}</p>
                <p class="mb-1 small"><strong>Alamat:</strong> ${business.address || '-'}</p>
                <p class="mb-1 small"><strong>Status:</strong> ${business.status || '-'}</p>
                <p class="mb-1 small"><strong>Sektor:</strong> ${business.sector ? business.sector.substring(0, 50) + '...' : '-'}</p>
                <p class="mb-1 small"><strong>Satker:</strong> ${orgInfo}</p>
                <p class="mb-1 small"><strong>Petugas:</strong> ${userInfo}</p>
                <p class="mb-0 small"><strong>Dibuat:</strong> ${createdAt}</p>
            `;
            
            document.getElementById(`${type}-business-content`).innerHTML = content;
        }

        // Function to populate similarity card
        function populateSimilarityCard(similarities) {
            const content = `
                <div class="d-flex justify-content-between align-items-center text-dark small">
                    <div class="text-center">
                        <strong class="text-primary">${(similarities.name_similarity * 100).toFixed(0)}%</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Name</div>
                    </div>
                    <div class="text-center">
                        <strong class="text-success">${(similarities.owner_similarity * 100).toFixed(0)}%</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Owner</div>
                    </div>
                    <div class="text-center">
                        <strong class="text-warning">${(similarities.confidence_score * 100).toFixed(0)}%</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Overall</div>
                    </div>
                    <div class="text-center">
                        <strong class="text-info">
                            ${similarities.distance_meters ? 
                                (similarities.distance_meters < 1000 ? 
                                    `${Math.round(similarities.distance_meters)}m` : 
                                    `${(similarities.distance_meters / 1000).toFixed(1)}km`) 
                                : '-'}
                        </strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Distance</div>
                    </div>
                    <div class="text-center">
                        <span class="badge ${similarities.duplicate_status === 'strong_duplicate' ? 'bg-danger' : 
                                           similarities.duplicate_status === 'weak_duplicate' ? 'bg-warning text-dark' : 'bg-secondary'}" style="font-size: 0.7rem;">
                            ${similarities.duplicate_status === 'strong_duplicate' ? 'Strong' :
                              similarities.duplicate_status === 'weak_duplicate' ? 'Weak' : 
                              'None'}
                        </span>
                        <div class="text-muted" style="font-size: 0.7rem;">Status</div>
                    </div>
                </div>
            `;
            
            document.getElementById('similarity-content').innerHTML = content;
        }

        // Handle modal show event to ensure proper map rendering
        document.getElementById('duplicateModal').addEventListener('shown.bs.modal', function () {
            // If map exists, invalidate size to ensure proper rendering
            if (businessMap) {
                setTimeout(() => {
                    businessMap.invalidateSize();
                }, 100);
            }
        });

        // Clean up map when modal is closed
        document.getElementById('duplicateModal').addEventListener('hidden.bs.modal', function () {
            // Reset to loading state
            document.getElementById('map-loading').style.display = 'block';
            document.getElementById('content-container').style.display = 'none';
            
            // Reset loading content
            document.getElementById('map-loading').innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memuat peta dan detail usaha...</p>
            `;
            
            // Destroy map instance to prevent memory leaks
            if (businessMap) {
                businessMap.remove();
                businessMap = null;
            }
        });

        // Global variables for duplicate action
        let currentCandidateId = null;

        // Action button event handlers
        document.getElementById('delete-business-a').addEventListener('click', function() {
            handleDuplicateAction('delete_center', 'Usaha A will be deleted. Are you sure?');
        });

        document.getElementById('delete-business-b').addEventListener('click', function() {
            handleDuplicateAction('delete_nearby', 'Usaha B will be deleted. Are you sure?');
        });

        document.getElementById('keep-both').addEventListener('click', function() {
            handleDuplicateAction('keep_both', 'Both businesses will be kept as separate entities. Are you sure?');
        });

        // Function to handle duplicate actions
        function handleDuplicateAction(action, confirmMessage) {
            // Determine title, message, icon and colors based on action type
            let title, htmlMessage, iconType, confirmButtonColor, confirmButtonText;
            
            if (action === 'delete_center') {
                // Delete Usaha A - red theme with trash icon
                title = 'Hapus Usaha A';
                htmlMessage = '<span style="color: #11cdef; font-weight: bold;">Usaha A</span> akan dihapus. Apakah yakin?';
                iconType = 'error';
                confirmButtonColor = '#dc3545';
                confirmButtonText = '<i class="fas fa-trash-alt me-1"></i>Yes, Delete!';
            } else if (action === 'delete_nearby') {
                // Delete Usaha B - red theme with trash icon
                title = 'Hapus Usaha B';
                htmlMessage = '<span style="color: #2dce89; font-weight: bold;">Usaha B</span> akan dihapus. Apakah yakin?';
                iconType = 'error';
                confirmButtonColor = '#dc3545';
                confirmButtonText = '<i class="fas fa-trash-alt me-1"></i>Yes, Delete!';
            } else if (action === 'keep_both') {
                // Keep both action - green theme with check icon
                title = 'Keep Keduanya';
                htmlMessage = 'Kedua usaha akan tetap disimpan sebagai taging terpisah. Apakah yakin?';
                iconType = 'success';
                confirmButtonColor = '#2dce89';
                confirmButtonText = '<i class="fas fa-check-circle me-1"></i>Yes, Keep Both!';
            }

            Swal.fire({
                title: title,
                html: htmlMessage,
                icon: iconType,
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    processDuplicateAction(action);
                }
            });
        }

        // Function to process the duplicate action
        function processDuplicateAction(action) {
            // Show loading state in main modal
            document.getElementById('content-container').style.display = 'none';
            document.getElementById('map-loading').style.display = 'block';
            document.getElementById('map-loading').innerHTML = `
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Memproses aksi...</p>
            `;

            // Make API call to process the action
            fetch(`/duplikat/action/${currentCandidateId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    action: action
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Close the modal
                bootstrap.Modal.getInstance(document.getElementById('duplicateModal')).hide();

                // Show success message
                Swal.fire({
                    title: 'Success!',
                    text: data.message || 'Action completed successfully',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    // Refresh the table
                    renderTable();
                });
            })
            .catch(error => {
                console.error('Error processing action:', error);
                
                // Hide loading and show content again
                document.getElementById('map-loading').style.display = 'none';
                document.getElementById('content-container').style.display = 'block';

                // Show error message
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to process the action. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
        }
    </script>
@endpush
