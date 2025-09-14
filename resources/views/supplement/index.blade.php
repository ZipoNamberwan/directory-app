@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        /* Collapsible chevron rotation */
        .toggle-btn .toggle-icon {
            transition: transform .2s ease;
        }

        .toggle-btn[aria-expanded="true"] .toggle-icon {
            transform: rotate(90deg);
        }

        /* Remove button shadow & outline */
        .toggle-btn,
        .toggle-btn:focus,
        .toggle-btn:active,
        .toggle-btn:hover {
            box-shadow: none !important;
            outline: none !important;
        }

        /* Header spacing */
        #matchingInfoHeader {
            gap: .4rem;
        }

        /* Smaller title styling */
        .matching-info-title {
            font-size: .9rem;
            font-weight: 600;
            line-height: 1.2;
        }

        .matching-info-title i {
            font-size: .85rem;
        }

        /* Chevron button sizing & centering */
        #matchingInfoToggle {
            width: 26px;
            height: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #matchingInfoToggle .toggle-icon {
            font-size: .85rem;
        }

        /* List item spacing */
        #matchingInfoBody ul li {
            margin-bottom: .4rem;
        }

        #matchingInfoBody ul li:last-child {
            margin-bottom: 0;
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
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Daftar Direktori Usaha Suplemen'])
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
                    <h5 class="text-capitalize">Daftar Usaha Suplemen</h5>
                    <div class="d-flex">
                        <form action="/suplemen/download/raw" class="me-2" method="POST">
                            @csrf
                            <input type="hidden" name="organization" id="organization_download">
                            <input type="hidden" name="market" id="market_download">
                            <button type="submit" class="btn btn-success mb-0 p-2">Download</button>
                        </form>
                        <button onclick="refresh()" class="btn btn-outline-success mb-0 p-2" data-bs-toggle="modal"
                            data-bs-target="#statusDialog">
                            <i class="fas fa-circle-info me-2"></i>
                            Status
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                {{-- Informational bullet list (collapsible) --}}
                <div class="alert border mb-4" role="alert" id="matchingInfoAlert" style="background-color:#f5f9ff;">
                    <div id="matchingInfoHeader" class="d-flex align-items-center mb-0" style="cursor:pointer;">
                        <button id="matchingInfoToggle" class="btn btn-sm p-0 border-0 bg-transparent toggle-btn mb-0"
                            type="button" data-bs-toggle="collapse" data-bs-target="#matchingInfoBody"
                            aria-expanded="false" aria-controls="matchingInfoBody" aria-label="Toggle info">
                            <i class="fas fa-chevron-right toggle-icon text-primary"></i>
                        </button>
                        <div class="matching-info-title text-dark mb-0 d-flex align-items-center">
                            <i class="fas fa-info-circle me-1 text-primary"></i>
                            <span>Info Matching Wilayah Terkecil (SLS)</span>
                        </div>
                    </div>
                    <div id="matchingInfoBody" class="collapse mt-3" data-bs-parent="#matchingInfoAlert">
                        <ul class="mb-0 ps-3 small">
                            <li>Matching wilayah dilakukan secara otomatis dengan jadwal <strong>setiap hari jam 3
                                    pagi</strong>.</li>
                            <li>Proses matching <strong>bisa berhasil bisa gagal</strong>, usaha yang gagal di matching bisa
                                dilihat dengan filter <strong>Status Matching Wilayah</strong>.</li>
                            <li>Jika <strong>matching gagal</strong> ada <strong>2 kemungkinan</strong>: - Koordinatnya
                                keliru (misal di laut) - Polygonnya yg keliru. Silakan kontak tim garda prov terkait ini
                                jika butuh bantuan.</li>
                            <li>Jika <strong>koordinat berubah</strong>, maka matching akan <strong>dilakukan lagi</strong>
                                untuk usaha tersebut.</li>
                            <li><strong>Menu download</strong> data juga sudah memasukkan wilayah.</li>
                        </ul>
                    </div>
                </div>
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
                    @hasrole('adminkab')
                        <div class="col-md-3">
                            <label class="form-control-label">Petugas</label>
                            <select id="user" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Petugas -- </option>
                                @foreach ($users as $user)
                                    <option value="{{ $user->id }}" {{ old('user') == $user->id ? 'selected' : '' }}>
                                        {{ $user->firstname }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endhasrole
                    <div class="col-md-3">
                        <label class="form-control-label">Tipe Projek</label>
                        <select id="projectType" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Tipe Projek -- </option>
                            @foreach ($projectTypes as $projectType)
                                <option value="{{ $projectType['value'] }}">
                                    {{ $projectType['name'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Status Matching Wilayah</label>
                        <select id="statusMatching" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Status -- </option>
                            <option value="all">Semua</option>
                            <option value="success">Sukses Matching</option>
                            <option value="failed">Gagal Matching</option>
                            <option value="notyet">Belum Matching</option>
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
                            Jumlah usaha yang difilter: <span id="total-records" class="fw-bold">0</span>
                        </p>
                    </div>
                </div>
                <div id="data-table"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ubah Data Usaha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Warning Message -->
                    <div class="alert alert-warning d-flex align-items-center mb-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div class="small">
                            <strong>Perhatian:</strong> Data yang sudah diubah melalui web, tidak bisa diubah kembali oleh petugas melalui KDM
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="save-business">
                        <span class="btn-text">Simpan</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="statusDialog" tabindex="-1" role="dialog" aria-labelledby="statusDialogLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Status Download</h5>
                    <button onclick="refresh()" class="btn btn-sm btn-outline-primary mb-0 p-2">
                        Refresh
                    </button>
                    {{-- <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> --}}
                </div>
                <div class="modal-body">
                    <table id="statusTable" class="align-items-center mb-0 text-sm">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">File</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Dibuat Oleh</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Status</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Dibuat pada</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
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
    <script src="/vendor/datatables/dataTables.min.js"></script>
    <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

    <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

    <script>
        const selectConfigs = [{
                selector: '#organization',
                placeholder: 'Pilih Satker'
            },
            {
                selector: '#market',
                placeholder: 'Pilih Pasar'
            },
            {
                selector: '#user',
                placeholder: 'Pilih Petugas'
            },
            {
                selector: '#projectType',
                placeholder: 'Pilih Tipe Projek'
            },
            {
                selector: '#statusMatching',
                placeholder: 'Pilih Status Matching'
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
                updateDownloadHidden()
            },
            '#market': () => {
                renderTable()
                updateDownloadHidden()
            },
            '#user': () => {
                renderTable()
            },
            '#projectType': () => {
                renderTable()
            },
            '#statusMatching': () => {
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

        function updateDownloadHidden() {
            document.getElementById('organization_download').value = $('#organization').val();
            document.getElementById('market_download').value = $('#market').val();
        }
        updateDownloadHidden();

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
            filterTypes = ['organization', 'market', 'user',
                'projectType', 'statusMatching',
                'regency', 'subdistrict',
                'village', 'sls', 'keyword'
            ];
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            table.setData('/suplemen/data?' + filterUrl);
        }

        function toTitleCase(input) {
            const str = String(input); // ensure it's a string
            return str
                .toLowerCase()
                .split(/\s+/)
                .filter(Boolean)
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(" ");
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

        function confirmDeleteBusiness(id, name) {
            event.preventDefault();
            Swal.fire({
                title: `Hapus Usaha Ini?`,
                html: `<strong>${name}</strong><br>Data yang sudah dihapus tidak bisa dikembalikan lagi.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya',
                cancelButtonText: 'Tidak',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu sebentar',
                        icon: 'info',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Make AJAX request to delete business
                    fetch(`/suplemen/${id}/delete`, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                                    'content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Remove row from table
                                if (table) {
                                    table.deleteRow(id);
                                }

                                // Show success message
                                Swal.fire({
                                    title: 'Berhasil!',
                                    text: 'Data usaha berhasil dihapus',
                                    icon: 'success',
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            } else {
                                // Show error message
                                Swal.fire({
                                    title: 'Gagal!',
                                    text: data.message || 'Terjadi kesalahan saat menghapus data',
                                    icon: 'error'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: 'Terjadi kesalahan sistem. Silakan coba lagi.',
                                icon: 'error'
                            });
                        });
                }
            })
        }

        function truncateText(text, maxLength) {
            if (text.length <= maxLength) {
                return text;
            }
            return text.substring(0, maxLength) + "...";
        }

        function refresh() {
            tableStatus.ajax.url('/status/data/2').load();
        }

        let tableStatus = new DataTable('#statusTable', {
            order: [],
            serverSide: true,
            processing: true,
            // deferLoading: 0,
            ajax: {
                url: '/status/data/2',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "id",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            if (row.status === 'success') {
                                if (row.file_has_deleted == 0) {
                                    return `
                                        <form class="my-2" action="/status/download/2" method="POST">
                                            @csrf
                                            <input type="hidden" name="id" value="${data}"> 
                                            <button class="btn btn-outline-secondary btn-sm ms-auto p-1 m-0" type="submit">
                                                <i class="fas fa-download mx-1"></i>
                                            </button>
                                        </form>
                                    `;
                                } else {
                                    return `<span class="badge bg-danger">Dihapus</span>`;
                                }
                            } else {
                                return '-';
                            }
                        }
                        return data;
                    }
                },
                {
                    responsivePriority: 3,
                    width: "10%",
                    data: "user.firstname",
                    type: "text",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "status",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {

                            var color = 'info'
                            if (data == 'success') {
                                color = 'success'
                            } else if (data == 'failed') {
                                color = 'danger'
                            } else if (data == 'success') {
                                color = 'success'
                            } else if (data == 'loading' || data == 'processing') {
                                color = 'secondary'
                            } else if (data == 'success with error') {
                                color = 'danger'
                            } else {
                                color = 'info'
                            }

                            return '<p class="mb-0"><span class="badge badge-small bg-' + color +
                                '">' +
                                data + '</span></p>';
                        }
                        return data;
                    }
                },
                {
                    responsivePriority: 3,
                    width: "10%",
                    data: "created_at",
                    type: "text",
                    render: function(data, type, row) {
                        return formatDate(data)
                    }
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "message",
                    type: "text",
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

    <script>
        // Global table variable
        let table;
        let organizationId = @json($organizationId);
        let canEditPermission = @json($canEdit);
        let canDeletePermission = @json($canDelete);

        function canEdit(permission, businessOrganizationId) {
            return permission && (organizationId == businessOrganizationId);
        }

        function canDelete(permission, businessOrganizationId) {
            return permission && (organizationId == businessOrganizationId);
        }

        // Define column configurations for different modes
        const getColumnConfig = (mode) => {
            const baseColumns = [{
                    title: "Nama",
                    field: "name",
                    responsive: 0,
                    formatter: function(cell) {
                        return `<div class="text-wrap font-weight-bold">${$("<div>").text(cell.getValue()).html()}</div>`;
                    }
                },
                {
                    title: "Detail",
                    field: "detail",
                    responsive: 1,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        let html = `<div class="my-1 small text-wrap lh-sm">`;

                        if (row.owner) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Pemilik:</span> <span class="fw-semibold text-dark">${row.owner}</span></div>`;
                        }
                        if (row.status) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Status:</span> <span class="fw-semibold text-dark">${row.status}</span></div>`;
                        }
                        if (row.address) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Alamat:</span> <span class="fw-semibold text-dark">${row.address}</span></div>`;
                        }
                        if (row.description) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Deskripsi:</span> <span class="fw-semibold text-dark">${truncateText(row.description, 60)}</span></div>`;
                        }
                        if (row.sector) {
                            html +=
                                `<div class="mb-1"><span class="text-muted">Sektor:</span> <span class="fw-semibold text-dark">${truncateText(row.sector, 40)}</span></div>`;
                        }
                        if (row.note) {
                            html +=
                                `<div><span class="text-muted">Catatan:</span> <span class="fw-semibold text-dark">${row.note}</span></div>`;
                        }

                        html += `</div>`;
                        return html;
                    }
                },
                {
                    title: "Lokasi",
                    field: "location",
                    responsive: 4,
                    hozAlign: "center",
                    formatter: function(cell) {
                        let row = cell.getRow().getData();
                        if (row.latitude && row.longitude) {
                            const lat = row.latitude;
                            const lng = row.longitude;
                            const mapsUrl = `https://www.google.com/maps?q=${lat},${lng}`;
                            return `
                        <a href="${mapsUrl}" target="_blank" class="d-inline-flex align-items-center justify-content-center 
                            rounded-circle bg-light text-primary" 
                            style="width:32px; height:32px;" title="Lihat Lokasi">
                            <i class="fas fa-map-marker-alt fa-lg"></i>
                        </a>`;
                        }
                        return "-";
                    }
                },
                {
                    title: "Wilayah",
                    field: "sls",
                    responsive: 2,
                    formatter: function(cell) {
                        let row = cell.getRow().getData();

                        if (row.match_level === 'failed') {
                            return `<div class="text-wrap lh-sm">
                        <div class="text-warning fw-semibold small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Tidak ada polygon yang pas untuk titik ini, bisa cek koordinatnya dulu
                        </div>
                    </div>`;
                        }

                        let areaId = "";
                        if (row.sls && row.sls.id) {
                            areaId = row.sls.id;
                        } else if (row.village && row.village.id) {
                            areaId = row.village.id;
                        } else if (row.subdistrict && row.subdistrict.id) {
                            areaId = row.subdistrict.id;
                        } else if (row.regency && row.regency.id) {
                            areaId = row.regency.id;
                        }

                        let areaNames = [];
                        if (row.regency && row.regency.name) {
                            areaNames.push(row.regency.name);
                        }
                        if (row.subdistrict && row.subdistrict.name) {
                            areaNames.push(row.subdistrict.name);
                        }
                        if (row.village && row.village.name) {
                            areaNames.push(row.village.name);
                        }
                        if (row.sls && row.sls.name) {
                            areaNames.push(row.sls.name);
                        }

                        let areaName = areaNames.length > 0 ? areaNames.join(", ") : "-";

                        if (!areaId && !areaName) return "-";

                        return `<div class="text-wrap lh-sm">
                    ${areaId ? `<div class="fw-semibold text-success">${toTitleCase(areaId)}</div>` : ""}
                    ${areaName !== "-" ? `<div class="small text-muted">${toTitleCase(areaName)}</div>` : ""}
                </div>`;
                    }
                },
                {
                    title: "Satker",
                    field: "organization",
                    responsive: 2,
                    formatter: function(cell) {
                        let data = cell.getValue();
                        if (!data) return "-";
                        return `<div class="text-wrap lh-sm"><span class="small mb-0">[${data.long_code}] ${data.name}</span></div>`;
                    }
                },
                {
                    title: "Petugas",
                    field: "user",
                    responsive: 3,
                    formatter: function(cell) {
                        const value = cell.getValue()?.firstname ?? "-";
                        return `<div class="small text-wrap lh-sm">${value}</div>`;
                    }
                },
                {
                    title: "Projek",
                    field: "project",
                    responsive: 5,
                    formatter: function(cell) {
                        let data = cell.getValue();
                        if (!data) return "-";
                        const projectName = (data.type === "kendedes mobile") ? "Kendedes Mobile" : "SWMAPS";
                        return `<div class="small text-wrap lh-sm">${projectName}</div>`;
                    }
                },
                {
                    title: "Dibuat Pada",
                    field: "created_at",
                    responsive: 6,
                    formatter: function(cell) {
                        return `<div class="small text-wrap lh-sm">${formatDate(cell.getValue())}</div>`;
                    }
                },
                {
                    title: "Aksi",
                    field: "id",
                    responsive: 7,
                    hozAlign: "center",
                    formatter: function(cell) {
                        let row = cell.getRow();
                        let rowData = row.getData();

                        // Get permissions from backend
                        const e = canEdit(canEditPermission, rowData.organization_id);
                        const d = canDelete(canDeletePermission, rowData.organization_id);
                        // Create container for buttons
                        let container = document.createElement("div");
                        container.className = "d-flex gap-1 justify-content-center";

                        if (e && rowData.not_confirmed_anomalies > 0) {
                            // TODO
                            return 'Menu Edit dan Hapus akan tersedia setelah Anomali diperbaiki'
                        } else {
                            // Edit button - visible if canEdit is true
                            if (e) {
                                let editButton = document.createElement("button");
                                editButton.className = "btn btn-success btn-sm px-2 py-1";
                                editButton.innerHTML = `<i class="fas fa-pencil-alt"></i>`;
                                editButton.onclick = function() {
                                    openEditDialog(rowData);
                                };
                                container.appendChild(editButton);
                            }

                            // Delete button - visible if canDelete is true
                            if (d) {
                                let deleteButton = document.createElement("button");
                                deleteButton.className = "btn btn-danger btn-sm px-2 py-1";
                                deleteButton.innerHTML = `<i class="fas fa-trash"></i>`;
                                deleteButton.onclick = function() {
                                    confirmDeleteBusiness(rowData.id, rowData.name);
                                };
                                container.appendChild(deleteButton);
                            }

                            // Return container if it has buttons, otherwise return "-"
                            return container.children.length > 0 ? container : "-";
                        }
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
                baseColumns[0].minWidth = 150;
                baseColumns[1].width = 250;
                baseColumns[1].minWidth = 200;
                baseColumns[2].width = 100;
                baseColumns[2].minWidth = 80;
                baseColumns[3].width = 200;
                baseColumns[3].minWidth = 150;
                baseColumns[4].width = 200;
                baseColumns[4].minWidth = 150;
                baseColumns[5].width = 150;
                baseColumns[5].minWidth = 120;
                baseColumns[6].width = 150;
                baseColumns[6].minWidth = 120;
                baseColumns[7].width = 150;
                baseColumns[7].minWidth = 120;
                baseColumns[8].width = 70;
                baseColumns[8].minWidth = 60;

                return [responsiveColumn, ...baseColumns];
            } else { // scroll horizontal
                // Set widths for horizontal scroll mode
                baseColumns[0].widthGrow = 3;
                baseColumns[0].minWidth = 150;
                baseColumns[1].width = 250;
                baseColumns[1].minWidth = 200;
                baseColumns[2].width = 100;
                baseColumns[2].minWidth = 80;
                baseColumns[3].width = 200;
                baseColumns[3].minWidth = 150;
                baseColumns[4].width = 200;
                baseColumns[4].minWidth = 150;
                baseColumns[5].width = 150;
                baseColumns[5].minWidth = 120;
                baseColumns[6].width = 150;
                baseColumns[6].minWidth = 120;
                baseColumns[7].width = 150;
                baseColumns[7].minWidth = 120;
                baseColumns[8].width = 70;
                baseColumns[8].minWidth = 60;

                return baseColumns;
            }
        };

        // Get table configuration based on mode
        const getTableConfig = (mode) => {
            const baseConfig = {
                height: "800px",
                layout: "fitColumns",
                ajaxURL: "/suplemen/data",
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

    <script>
        // Make entire header clickable to toggle collapse
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.getElementById('matchingInfoHeader');
            const btn = document.getElementById('matchingInfoToggle');
            if (header && btn) {
                header.addEventListener('click', function(e) {
                    // Avoid double-trigger if button itself clicked
                    if (!btn.contains(e.target)) {
                        btn.click();
                    }
                });
            }
        });
    </script>

    <script>
        // Define sectors data
        const sectors = [{
                code: 'A',
                name: 'A. Pertanian, Kehutanan dan Perikanan'
            },
            {
                code: 'B',
                name: 'B. Pertambangan dan Penggalian'
            },
            {
                code: 'C',
                name: 'C. Industri Pengolahan'
            },
            {
                code: 'D',
                name: 'D. Pengadaan Listrik, Gas, Uap/Air Panas Dan Udara Dingin'
            },
            {
                code: 'E',
                name: 'E. Treatment Air, Treatment Air Limbah, Treatment dan Pemulihan Material Sampah, dan Aktivitas Remediasi'
            },
            {
                code: 'F',
                name: 'F. Konstruksi'
            },
            {
                code: 'G',
                name: 'G. Perdagangan Besar Dan Eceran, Reparasi Dan Perawatan Mobil Dan Sepeda Motor'
            },
            {
                code: 'H',
                name: 'H. Pengangkutan dan Pergudangan'
            },
            {
                code: 'I',
                name: 'I. Penyediaan Akomodasi Dan Penyediaan Makan Minum'
            },
            {
                code: 'J',
                name: 'J. Informasi Dan Komunikasi'
            },
            {
                code: 'K',
                name: 'K. Aktivitas Keuangan dan Asuransi'
            },
            {
                code: 'L',
                name: 'L. Real Estat'
            },
            {
                code: 'M',
                name: 'M. Aktivitas Profesional, Ilmiah Dan Teknis'
            },
            {
                code: 'N',
                name: 'N. Aktivitas Penyewaan dan Sewa Guna Usaha Tanpa Hak Opsi, Ketenagakerjaan, Agen Perjalanan dan Penunjang Usaha Lainnya'
            },
            {
                code: 'O',
                name: 'O. Administrasi Pemerintahan, Pertahanan Dan Jaminan Sosial Wajib'
            },
            {
                code: 'P',
                name: 'P. Pendidikan'
            },
            {
                code: 'Q',
                name: 'Q. Aktivitas Kesehatan Manusia Dan Aktivitas Sosial'
            },
            {
                code: 'R',
                name: 'R. Kesenian, Hiburan Dan Rekreasi'
            },
            {
                code: 'S',
                name: 'S. Aktivitas Jasa Lainnya'
            },
            {
                code: 'T',
                name: 'T. Aktivitas Rumah Tangga Sebagai Pemberi Kerja; Aktivitas Yang Menghasilkan Barang Dan Jasa Oleh Rumah Tangga yang Digunakan untuk Memenuhi Kebutuhan Sendiri'
            },
            {
                code: 'U',
                name: 'U. Aktivitas Badan Internasional Dan Badan Ekstra Internasional Lainnya'
            }
        ];

        function getSectorFromValue(sectorValue) {
            if (!sectorValue) return null;

            // Get first letter of the sector value
            const firstLetter = sectorValue.charAt(0).toUpperCase();

            // Find matching sector
            return sectors.find(sector => sector.code === firstLetter) || null;
        }

        function generateEditForm(businessData) {
            // Get current sector
            const currentSector = getSectorFromValue(businessData.sector);

            // Generate sector options
            const sectorOptions = sectors.map(sector => {
                const isSelected = currentSector && currentSector.code === sector.code ? 'selected' : '';
                return `<option value="${sector.name}" ${isSelected}>${sector.name}</option>`;
            }).join('');

            return `
                <form id="editBusinessForm">
                    <input type="hidden" id="businessId" value="${businessData.id}">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="businessName" class="form-label">Nama Usaha <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="businessName" value="${businessData.name || ''}" required>
                            <div class="invalid-feedback" id="businessNameError"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="businessOwner" class="form-label">Pemilik Usaha</label>
                            <input type="text" class="form-control" id="businessOwner" value="${businessData.owner || ''}">
                            <div class="invalid-feedback" id="businessOwnerError"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="businessDescription" class="form-label">Deskripsi Usaha <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="businessDescription" rows="3" required>${businessData.description || ''}</textarea>
                            <div class="invalid-feedback" id="businessDescriptionError"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="businessAddress" class="form-label">Alamat Usaha</label>
                            <textarea class="form-control" id="businessAddress" rows="3">${businessData.address || ''}</textarea>
                            <div class="invalid-feedback" id="businessAddressError"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="buildingStatus" class="form-label">Status Bangunan <span class="text-danger">*</span></label>
                            <select class="form-control" id="buildingStatus" required>
                                <option value="">-- Pilih Status Bangunan --</option>
                                <option value="Tetap" ${businessData.status === 'Tetap' ? 'selected' : ''}>Tetap</option>
                                <option value="Tidak Tetap" ${businessData.status === 'Tidak Tetap' ? 'selected' : ''}>Tidak Tetap</option>
                            </select>
                            <div class="invalid-feedback" id="buildingStatusError"></div>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="businessSector" class="form-label">Sektor Usaha <span class="text-danger">*</span></label>
                            <select class="form-control" id="businessSector" required>
                                <option value="">-- Pilih Sektor Usaha --</option>
                                ${sectorOptions}
                            </select>
                            <div class="invalid-feedback" id="businessSectorError"></div>
                        </div>
                        
                        <div class="col-12">
                            <label for="businessNotes" class="form-label">Catatan</label>
                            <textarea class="form-control" id="businessNotes" rows="3">${businessData.note || ''}</textarea>
                            <div class="invalid-feedback" id="businessNotesError"></div>
                        </div>
                    </div>
                    
                    <div class="alert alert-danger mt-3 d-none" id="generalError"></div>
                </form>
            `;
        }

        function openEditDialog(rowData) {
            // Generate and populate form
            const formHtml = generateEditForm(rowData);
            
            // Find the warning message and preserve it, then add form after it
            const modalBody = document.querySelector('#editModal .modal-body');
            const warningAlert = modalBody.querySelector('.alert-warning');
            
            if (warningAlert) {
                // If warning exists, keep it and replace only content after it
                modalBody.innerHTML = warningAlert.outerHTML + formHtml;
            } else {
                // If no warning (shouldn't happen), replace entire content
                modalBody.innerHTML = formHtml;
            }

            // Initialize Select2 for selects
            $('#buildingStatus').select2({
                placeholder: 'Pilih Status Bangunan',
                allowClear: false,
                dropdownParent: $('#editModal')
            });

            $('#businessSector').select2({
                placeholder: 'Pilih Sektor Usaha',
                allowClear: false,
                dropdownParent: $('#editModal')
            });

            // Show modal
            let modal = new bootstrap.Modal(document.getElementById("editModal"));
            modal.show();
        }

        // Save business function
        document.getElementById('save-business').addEventListener('click', function() {
            const saveBtn = this;
            const form = document.getElementById('editBusinessForm');

            if (!form) return;

            // Clear previous errors
            clearValidationErrors();

            // Get form data
            const formData = {
                id: document.getElementById('businessId').value,
                name: document.getElementById('businessName').value.trim(),
                owner: document.getElementById('businessOwner').value.trim(),
                description: document.getElementById('businessDescription').value.trim(),
                address: document.getElementById('businessAddress').value.trim(),
                status: document.getElementById('buildingStatus').value,
                sector: document.getElementById('businessSector').value,
                note: document.getElementById('businessNotes').value.trim()
            };

            // Validate form data
            if (!validateBusinessForm(formData)) {
                return;
            }

            // Save the business data
            saveBusinessData(formData, saveBtn);
        });

        function validateBusinessForm(formData) {
            let hasErrors = false;

            // Validate required fields
            if (!formData.name) {
                showFieldError('businessName', 'Nama usaha wajib diisi');
                hasErrors = true;
            }

            if (!formData.description) {
                showFieldError('businessDescription', 'Deskripsi usaha wajib diisi');
                hasErrors = true;
            }

            if (!formData.status) {
                showFieldError('buildingStatus', 'Status bangunan wajib dipilih');
                hasErrors = true;
            }

            if (!formData.sector) {
                showFieldError('businessSector', 'Sektor usaha wajib dipilih');
                hasErrors = true;
            }

            // Return true if validation passes (no errors)
            return !hasErrors;
        }

        function saveBusinessData(formData, saveBtn) {
            // Show loading state
            saveBtn.disabled = true;
            saveBtn.querySelector('.btn-text').textContent = 'Menyimpan...';
            saveBtn.querySelector('.spinner-border').classList.remove('d-none');

            // Send update request
            fetch(`/suplemen/${formData.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(formData)
                })
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    if (data.success) {
                        // Update table row with new data
                        if (table && data.business) {
                            table.updateRow(data.business.id, data.business);

                            // Force redraw of the updated row to refresh formatted columns
                            const updatedRow = table.getRow(data.business.id);
                            if (updatedRow) {
                                updatedRow.reformat();
                            }
                        }

                        // Close modal
                        bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();

                    } else {
                        // Handle validation errors
                        if (data.errors) {
                            Object.keys(data.errors).forEach(field => {
                                const fieldMapping = {
                                    'name': 'businessName',
                                    'owner': 'businessOwner',
                                    'description': 'businessDescription',
                                    'address': 'businessAddress',
                                    'status': 'buildingStatus',
                                    'sector': 'businessSector',
                                    'note': 'businessNotes'
                                };

                                const mappedField = fieldMapping[field];
                                if (mappedField) {
                                    showFieldError(mappedField, data.errors[field][0]);
                                }
                            });
                        } else {
                            showGeneralError(data.message || 'Terjadi kesalahan saat menyimpan data');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showGeneralError('Terjadi kesalahan sistem. Silakan coba lagi.');
                })
                .finally(() => {
                    // Reset button state
                    saveBtn.disabled = false;
                    saveBtn.querySelector('.btn-text').textContent = 'Simpan';
                    saveBtn.querySelector('.spinner-border').classList.add('d-none');
                });
        }

        function clearValidationErrors() {
            // Remove error classes and clear error messages
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');
            document.getElementById('generalError').classList.add('d-none');
        }

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const errorDiv = document.getElementById(fieldId + 'Error');

            if (field && errorDiv) {
                field.classList.add('is-invalid');
                errorDiv.textContent = message;
            }
        }

        function showGeneralError(message) {
            const errorDiv = document.getElementById('generalError');
            if (errorDiv) {
                errorDiv.textContent = message;
                errorDiv.classList.remove('d-none');
            }
        }
    </script>
@endpush
