@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />

    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Color Scheme Configuration - Easy to modify */
        :root {
            /* Not Confirmed - Warm Amber (more professional than orange) */
            --color-notconfirmed: #f59e0b;
            --color-notconfirmed-light: rgba(245, 158, 11, 0.1);
            --color-notconfirmed-shadow: rgba(245, 158, 11, 0.35);

            /* Keep 1 - Ocean Blue (calmer, more trustworthy) */
            --color-keep1: #0ea5e9;
            --color-keep1-light: rgba(14, 165, 233, 0.1);
            --color-keep1-shadow: rgba(14, 165, 233, 0.35);

            /* Keep 2 - Fresh Green (vibrant but not harsh) */
            --color-keep2: #10b981;
            --color-keep2-light: rgba(16, 185, 129, 0.1);
            --color-keep2-shadow: rgba(16, 185, 129, 0.35);

            /* Keep All - Deep Indigo (sophisticated and distinctive) */
            --color-keepall: #6366f1;
            --color-keepall-light: rgba(99, 102, 241, 0.1);
            --color-keepall-shadow: rgba(99, 102, 241, 0.35);

            /* Delete Both - Danger Red */
            --color-delete: #dc2626;
            --color-delete-light: rgba(220, 38, 38, 0.1);
            --color-delete-shadow: rgba(220, 38, 38, 0.35);

            /* Secondary/Default - Gray */
            --color-secondary: #6c757d;
            --color-secondary-light: rgba(108, 117, 125, 0.1);
            --color-secondary-shadow: rgba(108, 117, 125, 0.35);
        }

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

        /* Enhanced radio button styles with color scheme */
        .btn-check:checked+.btn-outline-notconfirmed {
            background-color: var(--color-notconfirmed);
            border-color: var(--color-notconfirmed);
            color: white !important;
            box-shadow: 0 4px 15px var(--color-notconfirmed-shadow);
            transform: translateY(-2px);
        }

        .btn-check:checked+.btn-outline-notconfirmed * {
            color: white !important;
        }

        .btn-check:checked+.btn-outline-keep1 {
            background-color: var(--color-keep1);
            border-color: var(--color-keep1);
            color: white !important;
            box-shadow: 0 4px 15px var(--color-keep1-shadow);
            transform: translateY(-2px);
        }

        .btn-check:checked+.btn-outline-keep1 * {
            color: white !important;
        }

        .btn-check:checked+.btn-outline-keep2 {
            background-color: var(--color-keep2);
            border-color: var(--color-keep2);
            color: white !important;
            box-shadow: 0 4px 15px var(--color-keep2-shadow);
            transform: translateY(-2px);
        }

        .btn-check:checked+.btn-outline-keep2 * {
            color: white !important;
        }

        .btn-check:checked+.btn-outline-keepall {
            background-color: var(--color-keepall);
            border-color: var(--color-keepall);
            color: white !important;
            box-shadow: 0 4px 15px var(--color-keepall-shadow);
            transform: translateY(-2px);
        }

        .btn-check:checked+.btn-outline-keepall * {
            color: white !important;
        }

        .btn-check:checked+.btn-outline-delete {
            background-color: var(--color-delete);
            border-color: var(--color-delete);
            color: white !important;
            box-shadow: 0 4px 15px var(--color-delete-shadow);
            transform: translateY(-2px);
        }

        .btn-check:checked+.btn-outline-delete * {
            color: white !important;
        }

        .btn-outline-notconfirmed:hover,
        .btn-outline-keep1:hover,
        .btn-outline-keep2:hover,
        .btn-outline-keepall:hover,
        .btn-outline-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-check:focus+label {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Radio indicator styles */
        .btn-check:checked+label .radio-indicator::after {
            content: '✓';
        }

        .radio-indicator {
            transition: all 0.2s ease;
        }

        /* Warning tooltip styles */
        .tooltip-inner {
            max-width: 300px !important;
            background-color: #dc3545 !important;
            color: white !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            font-size: 0.875rem !important;
            line-height: 1.4 !important;
        }

        .tooltip.bs-tooltip-top .tooltip-arrow::before {
            border-top-color: #dc3545 !important;
        }

        .tooltip.bs-tooltip-bottom .tooltip-arrow::before {
            border-bottom-color: #dc3545 !important;
        }

        .tooltip.bs-tooltip-start .tooltip-arrow::before {
            border-left-color: #dc3545 !important;
        }

        .tooltip.bs-tooltip-end .tooltip-arrow::before {
            border-right-color: #dc3545 !important;
        }

        /* Warning icon hover effect */
        .fa-exclamation-triangle:hover {
            color: #dc3545 !important;
            transform: scale(1.1);
            transition: all 0.2s ease;
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
                                <option value="keepone">Salah Satu Usaha Di Keep</option>
                                <option value="keepall">Kedua Usaha Di Keep</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-control-label">Jenis Pair</label>
                            <select id="pairType" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Jenis Pair -- </option>
                                <option value="all">Semua</option>
                                <option value="supplementall">Semua Suplemen</option>
                                <option value="supplementmarket">Suplemen-Sentra Ekonomi</option>
                                <option value="marketall">Semua Sentra Ekonomi</option>
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
    <div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" style="height: 90vh; max-height: 90vh;">
            <div class="modal-content" style="height: 100%; display: flex; flex-direction: column;">
                <div class="modal-header text-dark" style="flex-shrink: 0;">
                    <h5 class="modal-title" id="duplicateModalLabel">
                        <i class="fas fa-balance-scale me-2"></i>Confirm Duplicate Businesses
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        style="filter: invert(1);"></button>
                </div>
                <div class="modal-body p-0" style="flex: 1; overflow-y: auto; max-height: calc(90vh - 80px);">
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
                            <div class="position-absolute"
                                style="bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 1000;">
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
                                    <div class="card shadow-sm h-100" style="border-color: var(--color-keep1);">
                                        <div class="card-header py-2"
                                            style="background-color: var(--color-keep1); color: white;">
                                            <h6 class="mb-0" style="color: white;"><i class="fas fa-building" style="color: white;"></i> Usaha A</h6>
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
                                    <div class="card shadow-sm h-100" style="border-color: var(--color-keep2);">
                                        <div class="card-header py-2"
                                            style="background-color: var(--color-keep2); color: white;">
                                            <h6 class="mb-0" style="color: white;"><i class="fas fa-building" style="color: white;"></i> Usaha B</h6>
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

                        <!-- Current Status Display -->
                        <div class="px-4 pb-2">
                            <div class="d-flex flex-column align-items-center justify-content-center py-2 px-3 text-center"
                                style="background-color: #f8f9fa; border-radius: 6px;">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-info-circle text-muted me-2"></i>
                                    <span class="text-muted fw-semibold me-2">Status Saat Ini:</span>
                                    <span id="current-status-display" class="text-dark fw-bold">Loading...</span>
                                </div>
                                <div id="confirmed-by-display" class="text-muted" style="display: none;">
                                    <!-- Confirmed by information will be shown here -->
                                </div>
                            </div>
                        </div>

                        <!-- Action Selection -->
                        <div class="px-4 pb-2">
                            <div class="row g-2 justify-content-center">
                                <div class="col-12 col-md-auto">
                                    <input class="btn-check" type="radio" name="duplicate-action" id="action-keep-a"
                                        value="keep_center">
                                    <label class="btn btn-outline-keep1 d-flex align-items-center justify-content-center"
                                        for="action-keep-a"
                                        style="min-width: 180px; padding: 10px 16px; border-width: 2px; border-radius: 8px; transition: all 0.3s ease; font-weight: 600; border-color: var(--color-keep1); color: var(--color-keep1);">
                                        <span class="radio-indicator me-2"
                                            style="width: 18px; height: 18px; border: 2px solid currentColor; border-radius: 3px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">
                                        </span>
                                        <i class="fas fa-check-circle me-2" style="font-size: 14px;"></i>
                                        <span>Keep Usaha A</span>
                                    </label>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <input class="btn-check" type="radio" name="duplicate-action" id="action-keep-b"
                                        value="keep_nearby">
                                    <label class="btn btn-outline-keep2 d-flex align-items-center justify-content-center"
                                        for="action-keep-b"
                                        style="min-width: 180px; padding: 10px 16px; border-width: 2px; border-radius: 8px; transition: all 0.3s ease; font-weight: 600; border-color: var(--color-keep2); color: var(--color-keep2);">
                                        <span class="radio-indicator me-2"
                                            style="width: 18px; height: 18px; border: 2px solid currentColor; border-radius: 3px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">
                                        </span>
                                        <i class="fas fa-check-circle me-2" style="font-size: 14px;"></i>
                                        <span>Keep Usaha B</span>
                                    </label>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <input class="btn-check" type="radio" name="duplicate-action" id="action-keep-both"
                                        value="keep_both">
                                    <label class="btn btn-outline-keepall d-flex align-items-center justify-content-center"
                                        for="action-keep-both"
                                        style="min-width: 180px; padding: 10px 16px; border-width: 2px; border-radius: 8px; transition: all 0.3s ease; font-weight: 600; border-color: var(--color-keepall); color: var(--color-keepall);">
                                        <span class="radio-indicator me-2"
                                            style="width: 18px; height: 18px; border: 2px solid currentColor; border-radius: 3px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">
                                        </span>
                                        <i class="fas fa-check-circle me-2" style="font-size: 14px;"></i>
                                        <span>Keep Keduanya</span>
                                    </label>
                                </div>
                                <div class="col-12 col-md-auto">
                                    <input class="btn-check" type="radio" name="duplicate-action" id="action-delete-both"
                                        value="delete_both">
                                    <label class="btn btn-outline-delete d-flex align-items-center justify-content-center"
                                        for="action-delete-both"
                                        style="min-width: 180px; padding: 10px 16px; border-width: 2px; border-radius: 8px; transition: all 0.3s ease; font-weight: 600; border-color: var(--color-delete); color: var(--color-delete);">
                                        <span class="radio-indicator me-2"
                                            style="width: 18px; height: 18px; border: 2px solid currentColor; border-radius: 3px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold;">
                                        </span>
                                        <i class="fas fa-trash-alt me-2" style="font-size: 14px;"></i>
                                        <span>Delete Keduanya</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="px-4 pb-3 text-center">
                            <button type="button" class="btn btn-primary me-2 px-4 py-2 fw-semibold rounded" id="confirm-action" disabled>
                                <i class="fas fa-check me-2"></i>Confirm
                            </button>
                            <button type="button" class="btn btn-secondary px-4 py-2 fw-semibold rounded" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i>Cancel
                            </button>
                        </div>
                    </div>
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
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    <script>
        // Color Scheme Configuration - Easy to modify
        const COLOR_SCHEME = {
            notconfirmed: {
                primary: '#fd7e14', // Orange
                light: 'rgba(253, 126, 20, 0.1)',
                shadow: 'rgba(253, 126, 20, 0.4)',
                class: 'warning', // Bootstrap class
                textClass: 'text-warning'
            },
            keep1: {
                primary: '#0d6efd', // Blue  
                light: 'rgba(13, 110, 253, 0.1)',
                shadow: 'rgba(13, 110, 253, 0.4)',
                class: 'primary', // Bootstrap class
                textClass: 'text-primary'
            },
            keep2: {
                primary: '#198754', // Green
                light: 'rgba(25, 135, 84, 0.1)',
                shadow: 'rgba(25, 135, 84, 0.4)',
                class: 'success', // Bootstrap class
                textClass: 'text-success'
            },
            keepall: {
                primary: '#6f42c1', // Purple
                light: 'rgba(111, 66, 193, 0.1)',
                shadow: 'rgba(111, 66, 193, 0.4)',
                class: 'info', // Using info for purple (closest match)
                textClass: 'text-info'
            },
            delete: {
                primary: '#dc2626', // Red
                light: 'rgba(220, 38, 38, 0.1)',
                shadow: 'rgba(220, 38, 38, 0.4)',
                class: 'danger', // Bootstrap class
                textClass: 'text-danger'
            },
            secondary: {
                primary: '#6c757d', // Gray
                light: 'rgba(108, 117, 125, 0.1)',
                shadow: 'rgba(108, 117, 125, 0.4)',
                class: 'secondary', // Bootstrap class
                textClass: 'text-secondary'
            }
        };

        const selectConfigs = [{
                selector: '#organization',
                placeholder: 'Pilih Satker'
            },
            {
                selector: '#status',
                placeholder: 'Pilih Status'
            },
            {
                selector: '#pairType',
                placeholder: 'Pilih Jenis Pair'
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
            '#pairType': () => {
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
                'village', 'sls', 'keyword', 'pairType'
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

                        return `<div style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; max-width: 100%;">
                                    <div class="fw-bold" title="${name}" style="line-height: 1.3; word-break: break-word;">${name}</div>
                                    <div class="text-muted small" title="Pemilik: ${owner || '-'}" style="line-height: 1.2; word-break: break-word;">Pemilik: ${owner || '-'}</div>
                                    <div class="text-muted small" title="${businessType}" style="line-height: 1.2; word-break: break-word;">${businessType}</div>
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

                        return `<div style="word-wrap: break-word; overflow-wrap: break-word; white-space: normal; max-width: 100%;">
                                    <div class="fw-bold" title="${name}" style="line-height: 1.3; word-break: break-word;">${name}</div>
                                    <div class="text-muted small" title="Pemilik: ${owner || '-'}" style="line-height: 1.2; word-break: break-word;">Pemilik: ${owner || '-'}</div>
                                    <div class="text-muted small" title="${businessType}" style="line-height: 1.2; word-break: break-word;">${businessType}</div>
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
                    title: "Status Pemeriksaan",
                    field: "status",
                    responsive: 3,
                    headerHozAlign: "center",
                    hozAlign: "center",
                    formatter: function(cell, formatterParams, onRendered) {
                        const status = cell.getValue();
                        let statusText = '';
                        let badgeStyle = '';
                        let bgColor = '';

                        switch (status) {
                            case 'notconfirmed':
                                statusText = 'Belum Dikonfirmasi';
                                bgColor = COLOR_SCHEME.notconfirmed.primary;
                                break;
                            case 'keep1':
                                statusText = 'Usaha A Di Keep';
                                bgColor = COLOR_SCHEME.keep1.primary;
                                break;
                            case 'keep2':
                                statusText = 'Usaha B Di Keep';
                                bgColor = COLOR_SCHEME.keep2.primary;
                                break;
                            case 'keepall':
                                statusText = 'Kedua Usaha Di Keep';
                                bgColor = COLOR_SCHEME.keepall.primary;
                                break;
                            case 'deleteall':
                                statusText = 'Kedua Usaha Dihapus';
                                bgColor = COLOR_SCHEME.delete.primary;
                                break;
                            default:
                                statusText = status || '-';
                                bgColor = COLOR_SCHEME.secondary.primary;
                        }

                        badgeStyle = `background-color: ${bgColor}; color: white; font-size: 0.75rem;`;
                        return `<span class="badge" style="${badgeStyle}">${statusText}</span>`;
                    }
                },
                {
                    title: "Dikonfirmasi Oleh",
                    field: "last_confirmed_by",
                    responsive: 3,
                    headerHozAlign: "center",
                    hozAlign: "center",
                    formatter: function(cell, formatterParams, onRendered) {
                        const user = cell.getValue();
                        if (!user) return '-';

                        const name = user.firstname || '-';
                        const orgId = user.organization_id || '-';
                        return `${name} (${orgId})`;
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
                baseColumns[0].widthGrow = 3;
                baseColumns[0].minWidth = 220;
                baseColumns[1].widthGrow = 3;
                baseColumns[1].minWidth = 220;
                baseColumns[3].width = 100;
                baseColumns[3].minWidth = 80;
                baseColumns[4].width = 100;
                baseColumns[4].minWidth = 80;
                baseColumns[5].width = 100;
                baseColumns[5].minWidth = 80;

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
                baseColumns[5].width = 100;
                baseColumns[5].minWidth = 80;

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
                    businessMap = L.map('business-map', {
                        zoomControl: false,      // Disable zoom controls
                        scrollWheelZoom: false,  // Disable scroll wheel zoom
                        doubleClickZoom: false,  // Disable double click zoom
                        touchZoom: false,        // Disable touch zoom
                        keyboard: false,         // Disable keyboard zoom
                        dragging: true          // Keep dragging enabled
                    });
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

                // Create custom icons with color scheme
                const centerIcon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div style="background-color: ${COLOR_SCHEME.keep1.primary}; border: 3px solid white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="fas fa-building text-white" style="font-size: 12px;"></i></div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                const nearbyIcon = L.divIcon({
                    className: 'custom-marker',
                    html: `<div style="background-color: ${COLOR_SCHEME.keep2.primary}; border: 3px solid white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><i class="fas fa-building text-white" style="font-size: 12px;"></i></div>`,
                    iconSize: [30, 30],
                    iconAnchor: [15, 15]
                });

                // Add markers
                const centerMarker = L.marker(centerLatLng, {
                    icon: centerIcon
                }).addTo(businessMap);
                const nearbyMarker = L.marker(nearbyLatLng, {
                    icon: nearbyIcon
                }).addTo(businessMap);

                // Add connecting line
                const line = L.polyline([centerLatLng, nearbyLatLng], {
                    color: similarities.duplicate_status === 'strong_duplicate' ? '#dc3545' : similarities
                        .duplicate_status === 'weak_duplicate' ? '#ffc107' : '#6c757d',
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

            // Populate business detail cards with candidate data for comparison
            populateBusinessCard('center', centerBusiness, {
                candidate_name: rowData.center_business_name || '',
                candidate_owner: rowData.center_business_owner || ''
            });
            populateBusinessCard('nearby', nearbyBusiness, {
                candidate_name: rowData.nearby_business_name || '',
                candidate_owner: rowData.nearby_business_owner || ''
            });
            populateSimilarityCard(similarities);

            // Update current status display and set radio buttons
            updateCurrentStatusDisplay(rowData.status || 'notconfirmed', rowData.last_confirmed_by);

            // Reset radio buttons first
            document.querySelectorAll('input[name="duplicate-action"]').forEach(radio => {
                radio.checked = false;
            });

            // Auto-select radio button based on current status
            const currentStatus = rowData.status || 'notconfirmed';
            let radioToSelect = null;

            switch (currentStatus) {
                case 'keep1':
                    radioToSelect = document.getElementById('action-keep-a');
                    break;
                case 'keep2':
                    radioToSelect = document.getElementById('action-keep-b');
                    break;
                case 'keepall':
                    radioToSelect = document.getElementById('action-keep-both');
                    break;
                case 'deleteall':
                    radioToSelect = document.getElementById('action-delete-both');
                    break;
                default:
                    // For 'notconfirmed' or other statuses, don't pre-select anything
                    radioToSelect = null;
            }

            if (radioToSelect) {
                radioToSelect.checked = true;
                document.getElementById('confirm-action').disabled = false;
            } else {
                document.getElementById('confirm-action').disabled = true;
            }
        }

        // Function to populate individual business card
        function populateBusinessCard(type, business, candidateData = null) {
            // Check if business is deleted
            const isDeleted = business.deleted_at !== null;

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

            // Helper function to create warning icon with tooltip
            const createWarningIcon = (currentValue, candidateValue, fieldName) => {
                // Don't show warning if candidateData is not available
                if (!candidateData) {
                    return '';
                }
                
                // Normalize values for comparison (treat null/undefined as empty string)
                const normalizedCurrent = (currentValue || '').toString().trim();
                const normalizedCandidate = (candidateValue || '').toString().trim();
                
                // Don't show warning if values are the same
                if (normalizedCurrent === normalizedCandidate) {
                    return '';
                }
                
                const tooltipId = `tooltip-${type}-${fieldName}-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
                
                // Get field display name
                const fieldDisplayName = fieldName === 'name' ? 'Nama Usaha' : 'Pemilik';
                
                // Create appropriate tooltip message based on the change type
                let tooltipMessage;
                if (normalizedCandidate === '' && normalizedCurrent !== '') {
                    tooltipMessage = `${fieldDisplayName} telah berubah: <br><strong>'${normalizedCurrent}'</strong><br>(sebelumnya kosong)`;
                } else if (normalizedCandidate !== '' && normalizedCurrent === '') {
                    tooltipMessage = `${fieldDisplayName} telah dihapus: <br><strong>'${normalizedCandidate}'</strong><br>(sekarang kosong)`;
                } else {
                    tooltipMessage = `${fieldDisplayName} telah berubah dari: <br><strong>'${normalizedCandidate}'</strong><br>menjadi: <br><strong>'${normalizedCurrent}'</strong>`;
                }
                
                return ` <i class="fas fa-exclamation-triangle text-warning ms-1" 
                           style="font-size: 0.8rem; cursor: help;" 
                           data-bs-toggle="tooltip" 
                           data-bs-placement="top" 
                           data-bs-html="true"
                           data-bs-title="${tooltipMessage}"
                           id="${tooltipId}"></i>`;
            };

            // Get current values and candidate values for comparison
            // Don't use fallback '-' here for comparison, use actual values
            const currentName = business.name || '';
            const currentOwner = business.owner || '';
            const candidateName = candidateData?.candidate_name || '';
            const candidateOwner = candidateData?.candidate_owner || '';

            // Create warning icons for changed values
            const nameWarning = createWarningIcon(currentName, candidateName, 'name');
            const ownerWarning = createWarningIcon(currentOwner, candidateOwner, 'owner');

            // For display purposes, show '-' when empty
            const displayName = currentName || '-';
            const displayOwner = currentOwner || '-';

            const businessColor = type === 'center' ? COLOR_SCHEME.keep1.primary : COLOR_SCHEME.keep2.primary;
            const content = `
                <h6 class="fw-bold mb-1" style="color: ${businessColor};">${displayName}${nameWarning}</h6>
                <p class="mb-1 small"><strong>Pemilik:</strong> ${displayOwner}${ownerWarning}</p>
                <p class="mb-1 small"><strong>Alamat:</strong> ${business.address || '-'}</p>
                <p class="mb-1 small"><strong>Status:</strong> ${business.status || '-'}</p>
                <p class="mb-1 small"><strong>Sektor:</strong> ${business.sector ? business.sector.substring(0, 50) + '...' : '-'}</p>
                <p class="mb-1 small"><strong>Satker:</strong> ${orgInfo}</p>
                <p class="mb-1 small"><strong>Petugas:</strong> ${userInfo}</p>
                <p class="mb-0 small"><strong>Dibuat:</strong> ${createdAt}</p>
            `;

            // Get the card elements
            const cardElement = document.getElementById(`${type}-business-content`).closest('.card');
            const headerElement = cardElement.querySelector('.card-header h6');

            // Update content
            document.getElementById(`${type}-business-content`).innerHTML = content;

            // Initialize tooltips for the newly added warning icons
            setTimeout(() => {
                const tooltipElements = cardElement.querySelectorAll('[data-bs-toggle="tooltip"]');
                tooltipElements.forEach(element => {
                    new bootstrap.Tooltip(element);
                });
            }, 100);

            // Update header and styling based on deletion status
            if (isDeleted) {
                // Add DELETED label to header
                const businessLabel = type === 'center' ? 'Usaha A' : 'Usaha B';
                headerElement.innerHTML =
                    `<i class="fas fa-building"></i> ${businessLabel} <span class="badge bg-danger ms-2" style="font-size: 0.7rem;">DELETED</span>`;

                // Apply fading effect to the entire card
                cardElement.style.opacity = '0.5';
                cardElement.style.filter = 'grayscale(20%)';
            } else {
                // Reset to normal state
                const businessLabel = type === 'center' ? 'Usaha A' : 'Usaha B';
                headerElement.innerHTML = `<i class="fas fa-building"></i> ${businessLabel}`;
                cardElement.style.opacity = '1';
                cardElement.style.filter = 'none';
            }
        }

        // Function to populate similarity card
        function populateSimilarityCard(similarities) {
            // Calculate percentage scores
            const nameScore = (similarities.name_similarity * 100).toFixed(0);
            const ownerScore = (similarities.owner_similarity * 100).toFixed(0);
            const overallScore = (similarities.confidence_score * 100).toFixed(0);

            // Apply color grading logic for name similarity
            let nameColor = 'success';
            if (nameScore < 75) {
                nameColor = 'danger';
            } else if (nameScore < 90) {
                nameColor = 'warning';
            }

            // Apply color grading logic for owner similarity
            let ownerColor = 'success';
            if (ownerScore < 75) {
                ownerColor = 'danger';
            } else if (ownerScore < 90) {
                ownerColor = 'warning';
            }

            // Apply color grading logic for overall score
            let overallColor = 'success';
            if (overallScore < 75) {
                overallColor = 'danger';
            } else if (overallScore < 90) {
                overallColor = 'warning';
            }

            const content = `
                <div class="d-flex justify-content-between align-items-center text-dark small">
                    <div class="text-center">
                        <strong class="text-${nameColor}">${nameScore}%</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Name</div>
                    </div>
                    <div class="text-center">
                        <strong class="text-${ownerColor}">${ownerScore}%</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Owner</div>
                    </div>
                    <div class="text-center">
                        <strong class="text-${overallColor}">${overallScore}%</strong>
                        <div class="text-muted" style="font-size: 0.7rem;">Keseluruhan</div>
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
                </div>
            `;

            document.getElementById('similarity-content').innerHTML = content;
        }

        // Handle modal show event to ensure proper map rendering
        document.getElementById('duplicateModal').addEventListener('shown.bs.modal', function() {
            // If map exists, invalidate size to ensure proper rendering
            if (businessMap) {
                setTimeout(() => {
                    businessMap.invalidateSize();
                }, 100);
            }
        });

        // Clean up map when modal is closed
        document.getElementById('duplicateModal').addEventListener('hidden.bs.modal', function() {
            // Clean up any existing tooltips to prevent memory leaks
            const tooltipElements = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipElements.forEach(element => {
                const tooltip = bootstrap.Tooltip.getInstance(element);
                if (tooltip) {
                    tooltip.dispose();
                }
            });

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
        let currentStatus = null;

        // Radio button change handler
        document.querySelectorAll('input[name="duplicate-action"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const confirmButton = document.getElementById('confirm-action');
                if (this.checked) {
                    confirmButton.disabled = false;
                } else {
                    confirmButton.disabled = true;
                }
            });
        });

        // Confirm action button handler
        document.getElementById('confirm-action').addEventListener('click', function() {
            const selectedAction = document.querySelector('input[name="duplicate-action"]:checked');
            if (selectedAction) {
                handleDuplicateAction(selectedAction.value);
            }
        });

        // Function to update current status display
        function updateCurrentStatusDisplay(status, lastConfirmedBy = null) {
            const statusDisplay = document.getElementById('current-status-display');
            const confirmedByDisplay = document.getElementById('confirmed-by-display');
            let statusText = '';
            let textColor = '';

            switch (status) {
                case 'notconfirmed':
                    statusText = 'Belum Dikonfirmasi';
                    textColor = COLOR_SCHEME.notconfirmed.primary;
                    break;
                case 'keep1':
                    statusText = 'Usaha A Di Keep';
                    textColor = COLOR_SCHEME.keep1.primary;
                    break;
                case 'keep2':
                    statusText = 'Usaha B Di Keep';
                    textColor = COLOR_SCHEME.keep2.primary;
                    break;
                case 'keepall':
                    statusText = 'Kedua Usaha Di Keep';
                    textColor = COLOR_SCHEME.keepall.primary;
                    break;
                case 'deleteall':
                    statusText = 'Kedua Usaha Dihapus';
                    textColor = COLOR_SCHEME.delete.primary;
                    break;
                default:
                    statusText = status || 'Tidak Diketahui';
                    textColor = COLOR_SCHEME.secondary.primary;
            }

            // Update status display (first line)
            statusDisplay.innerHTML = `<span class="fw-bold" style="color: ${textColor};">${statusText}</span>`;

            // Update confirmed by display (second line)
            if (lastConfirmedBy && status !== 'notconfirmed') {
                const userName = lastConfirmedBy.firstname || 'Unknown';
                const orgId = lastConfirmedBy.organization_id || '-';
                confirmedByDisplay.innerHTML = `<small>Dikonfirmasi terakhir oleh: <span class="fw-bold">${userName} (${orgId})</span></small>`;
                confirmedByDisplay.style.display = 'block';
            } else {
                confirmedByDisplay.style.display = 'none';
            }

            currentStatus = status;
        }

        // Function to handle duplicate actions
        function handleDuplicateAction(action) {
            // Determine title, message, icon and colors based on action type
            let title, htmlMessage, iconType, confirmButtonColor, confirmButtonText;

            if (action === 'keep_center') {
                // Keep Usaha A - blue theme with check icon
                title = 'Keep Usaha A';
                htmlMessage =
                    `<span style="color: ${COLOR_SCHEME.keep1.primary}; font-weight: bold;">Usaha A</span> akan di-keep dan <span style="color: ${COLOR_SCHEME.keep2.primary}; font-weight: bold;">Usaha B</span> akan dihapus. Apakah yakin?`;
                iconType = 'success';
                confirmButtonColor = COLOR_SCHEME.keep1.primary;
                confirmButtonText = '<i class="fas fa-check-circle me-1"></i>Yes, Keep A!';
            } else if (action === 'keep_nearby') {
                // Keep Usaha B - green theme with check icon
                title = 'Keep Usaha B';
                htmlMessage =
                    `<span style="color: ${COLOR_SCHEME.keep2.primary}; font-weight: bold;">Usaha B</span> akan di-keep dan <span style="color: ${COLOR_SCHEME.keep1.primary}; font-weight: bold;">Usaha A</span> akan dihapus. Apakah yakin?`;
                iconType = 'success';
                confirmButtonColor = COLOR_SCHEME.keep2.primary;
                confirmButtonText = '<i class="fas fa-check-circle me-1"></i>Yes, Keep B!';
            } else if (action === 'keep_both') {
                // Keep both action - purple theme with check icon
                title = 'Keep Keduanya';
                htmlMessage = 'Kedua usaha akan tetap disimpan sebagai entitas terpisah. Apakah yakin?';
                iconType = 'success';
                confirmButtonColor = COLOR_SCHEME.keepall.primary;
                confirmButtonText = '<i class="fas fa-check-circle me-1"></i>Yes, Keep Keduanya!';
            } else if (action === 'delete_both') {
                // Delete both action - red theme with trash icon
                title = 'Delete Keduanya';
                htmlMessage = `<span style="color: ${COLOR_SCHEME.delete.primary}; font-weight: bold;">Kedua usaha akan dihapus permanen!</span> Tindakan ini tidak dapat dibatalkan. Apakah yakin?`;
                iconType = 'warning';
                confirmButtonColor = COLOR_SCHEME.delete.primary; // Red color
                confirmButtonText = '<i class="fas fa-trash-alt me-1"></i>Yes, Delete Keduanya!';
            }

            Swal.fire({
                title: title,
                html: htmlMessage,
                icon: iconType,
                showCancelButton: true,
                confirmButtonColor: confirmButtonColor,
                cancelButtonColor: '#6c757d',
                confirmButtonText: confirmButtonText,
                cancelButtonText: '<i class="fas fa-times me-1"></i>Cancel',
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

            // Map action to status values
            let status;
            switch (action) {
                case 'keep_center':
                    status = 'keep1';
                    break;
                case 'keep_nearby':
                    status = 'keep2';
                    break;
                case 'keep_both':
                    status = 'keepall';
                    break;
                case 'delete_both':
                    status = 'deleteall';
                    break;
                default:
                    status = 'keepall';
            }

            // Make API call to process the action
            fetch(`/duplikat/pair/${currentCandidateId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        status: status
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

                    // Update the specific row with only the changed fields
                    const row = table.getRow(currentCandidateId);
                    if (row && data.candidate) {
                        const candidate = data.candidate;
                        const rowData = row.getData();

                        // Only update the fields that actually change
                        rowData.status = candidate.status;
                        rowData.last_confirmed_by = candidate.last_confirmed_by;

                        row.update(rowData);
                    }

                    // Show success message
                    Swal.fire({
                        title: 'Success!',
                        text: data.message || 'Action completed successfully',
                        icon: 'success',
                        confirmButtonText: 'OK'
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
