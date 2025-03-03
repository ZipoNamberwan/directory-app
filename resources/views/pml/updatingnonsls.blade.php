@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <style>
        /* Responsive pagination container */
        .pagination-wrapper {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            /* gap: 10px; */
            justify-content: center;
        }

        /* Pagination container to allow wrapping */
        .pagination {
            display: flex;
            flex-wrap: wrap;
            /* gap: 5px; */
            justify-content: center;
            /* margin-bottom: 10px; */
        }

        /* Visibility control */
        .pagination-wrapper.hidden {
            opacity: 0;
            visibility: hidden;
            height: 0;
            overflow: hidden;
        }

        /* Ensure page items can wrap */
        .page-item {
            /* margin: 2px; */
            margin: 0px;
        }

        /* Mobile-first responsive design */
        @media (max-width: 576px) {
            .pagination-wrapper {
                flex-direction: column;
                align-items: stretch;
            }

            .pagination {
                justify-content: center;
                /* gap: 5px; */
            }

            .page-length-control {
                display: flex;
                justify-content: center;
                margin-top: 10px;
            }
        }

        /* Flexible page length control */
        .page-length-control {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .page-length-control select {
            flex-grow: 1;
            max-width: 100px;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(128, 128, 128, 0.5);
            /* Semi-transparent gray overlay */
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        #directorylist {
            position: relative;
        }
    </style>
    <style>
        .full-screen-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Pemutakhiran'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <p class="mb-0">Pilih Wilayah</p>
                </div>
            </div>
            <div class="card-body pt-1">
                <div class="row mb-1">
                    <div class="col-md-3">
                        <label class="form-control-label">Level <span class="text-danger">*</span></label>
                        <select style="width: 100%;" id="level" name="level" class="form-control"
                            data-toggle="select">
                            <option value="0" disabled selected> -- Pilih level -- </option>
                            <option value="regency">
                                Kabupaten
                            </option>
                            <option value="subdistrict">
                                Kecamatan
                            </option>
                            <option value="village">
                                Desa
                            </option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                        <select disabled style="width: 100%;" id="subdistrict" name="subdistrict" class="form-control"
                            data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Kecamatan -- </option>
                            <option value="all">Semua Kecamatan</option>
                            @foreach ($subdistricts as $subdistrict)
                                <option value="{{ $subdistrict->id }}"
                                    {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                    [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                        <select disabled id="village" name="village" class="form-control" data-toggle="select"
                            name="village"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Status <span class="text-danger">*</span></label>
                        <select disabled style="width: 100%;" id="status" name="status" class="form-control"
                            data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Status -- </option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}" {{ old('status') == $status->id ? 'selected' : '' }}>
                                    [{{ $status->code }}] {{ $status->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label" for="search">Nama <span class="text-danger">*</span></label>
                        <input disabled type="text" name="search" class="form-control mb-0" id="search"
                            placeholder="Cari...">
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-2">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <p class="mb-0">Daftar Direktori Tidak Sampai Level SLS</p>
                    <button id="add-button" onclick="openAddModal()"
                        class="btn btn-primary btn-sm ms-auto p-2 m-0">Tambah</button>
                </div>
            </div>
            <div class="card-body">
                <div id="directorylist" class="row mb-3">

                </div>
                <div id="paginationContainer"></div>
                <div id="loadingOverlay" class="loading-overlay">
                    <div class="loading-spinner"></div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="updateDirectoryModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalCenterTitle">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header pb-1">
                        <div class="row">
                            {{-- <div class="col-12">
                                <h5 id="modaltitle">Modal title</h5>
                            </div> --}}
                            <div class="col-12">
                                <label class="form-control-label">Nama Usaha <span class="text-danger">*</span></label>
                                <input type="text" id="nameUpdate" name="nameUpdate" class="form-control mb-0"
                                    placeholder="Nama Usaha">
                            </div>
                            <div class="col-12">
                                <label class="form-control-label">Nama Pemilik/Pengusaha</label>
                                <input type="text" id="ownerUpdate" name="ownerUpdate" class="form-control mb-0"
                                    placeholder="Nama Pemilik/Pengusaha">
                            </div>
                            <div class="col-12">
                                <label class="form-control-label">Alamat</label>
                                <div>
                                    <span class="mb-0" style="font-size: 0.75rem;" id="modalsubtitle">Modal
                                        title</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="businessId" />
                    <div class="modal-body pt-0 mt-2" style="height: auto;">
                        <div class="row">
                            <div id="statusCol" class="col-12">
                                <label class="form-control-label">Status <span class="text-danger">*</span></label>
                                <select id="statusUpdate" name="status" class="form-control" data-toggle="select"
                                    required>
                                    <option value="0" disabled selected> -- Pilih Status -- </option>
                                    @foreach ($statuses as $status)
                                        @if ($status->name != 'Baru')
                                            <option value="{{ $status->id }}">{{ $status->name }}</option>
                                        @endif
                                    @endforeach
                                </select>
                            </div>
                            <div id="addressCol" class="col-12">
                                <label class="form-control-label" for="addressUpdate">Alamat Lengkap <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="addressUpdate" class="form-control mb-0" id="addressUpdate"
                                    placeholder="Alamat Lengkap">
                            </div>
                            <div id="sourceCol" class="col-12">
                                <label class="form-control-label" for="sourceUpdate">Sumber Data <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="sourceUpdate" class="form-control mb-0" id="sourceUpdate"
                                    placeholder="Sumber Data">
                            </div>
                            <div id="slsColFiled" class="col-12 my-2">
                                <label class="form-control-label" id="areaUpdateLabel"></label>
                                <p class="mb-1 text-sm text-muted" id="subdistrictUpdateLabel"></p>
                                <p class="mb-1 text-sm text-muted" id="villageUpdateLabel"></p>
                                <p class="mb-1 text-sm text-muted" id="slsUpdateLabel"></p>
                                <div id="switchAreaLabel" class="form-check form-switch mt-2">
                                    <input onchange="onChangeArea()" class="form-check-input" type="checkbox"
                                        role="switch" name="switchArea" id="switchArea">
                                    <label class="form-check-label" for="flexSwitchCheckDefault">Ganti Wilayah</label>
                                </div>
                            </div>
                            <div id="subdistrictCol" class="col-12">
                                <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                                <select id="subdistrictUpdate" name="subdistrictUpdate" class="form-control"
                                    data-toggle="select">
                                    <option value="0" disabled selected> -- Pilih Kecamatan -- </option>
                                    @foreach ($subdistricts as $subdistrict)
                                        <option value="{{ $subdistrict->id }}"
                                            {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                            [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div id="villageCol" class="col-12">
                                <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                                <select id="villageUpdate" name="villageUpdate" class="form-control"
                                    data-toggle="select" name="village"></select>
                            </div>
                            <div id="slsCol" class="col-12">
                                <label class="form-control-label">SLS <span class="text-danger">*</span></label>
                                <select id="slsUpdate" name="slsUpdate" class="form-control"
                                    data-toggle="select"></select>
                            </div>
                        </div>

                        <div id="update-error">
                            <p class="error-feedback mb-0 mt-2">
                                Ada yang keliru
                            </p>
                        </div>
                        <div>
                            <p id="loading-save" style="visibility: hidden;" class="text-warning mt-3">Loading...</p>
                        </div>
                    </div>

                    <div class="modal-footer pt-0">
                        <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">Batal</button>
                        <button onclick="onSave()" type="button" class="btn btn-primary mb-0">Simpan</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="addDirectoryModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalCenterTitle">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header pb-1">
                        <div>
                            <h5>Tambah Usaha</h5>
                            <span class="mb-0" style="font-size: 0.75rem;">Menu tambah usaha baru</span>
                        </div>
                        <!-- <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span>&times;</span></button> -->
                    </div>
                    <div class="modal-body pt-0 mt-2" style="height: auto;">
                        <div class="row">
                            <div class="col-12">
                                <label class="form-control-label" for="nameAdd">Nama Usaha <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="nameAdd" class="form-control mb-0" id="nameAdd"
                                    placeholder="Nama Usaha">
                            </div>
                            <div class="col-12">
                                <label class="form-control-label" for="ownerAdd">Nama Pemilik/Pengusaha <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="ownerAdd" class="form-control mb-0" id="ownerAdd"
                                    placeholder="Nama Pemilik/Pengusaha">
                            </div>
                            <div class="col-12">
                                <label class="form-control-label" for="addressAdd">Alamat Lengkap <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="addressAdd" class="form-control mb-0" id="addressAdd"
                                    placeholder="Alamat Lengkap">
                            </div>
                            <div class="col-12">
                                <label class="form-control-label" for="sourceAdd">Sumber <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="sourceAdd" class="form-control mb-0" id="sourceAdd"
                                    placeholder="Sumber Data">
                            </div>
                            <div class="col-12">
                                <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                                <select id="subdistrictAdd" name="subdistrictAdd" class="form-control"
                                    data-toggle="select">
                                    <option value="0" disabled selected> -- Pilih Kecamatan -- </option>
                                    @foreach ($subdistricts as $subdistrict)
                                        <option value="{{ $subdistrict->id }}"
                                            {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                            [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                                <select id="villageAdd" name="villageAdd" class="form-control" data-toggle="select"
                                    name="village"></select>
                            </div>
                            <div class="col-12">
                                <label class="form-control-label">SLS <span class="text-danger">*</span></label>
                                <select id="slsAdd" name="slsAdd" class="form-control"
                                    data-toggle="select"></select>
                            </div>
                        </div>

                        <div id="add-error">
                            <p class="error-feedback mb-0 mt-2">
                                Ada yang Belum diisi
                            </p>
                        </div>
                        <div>
                            <p id="loading-add" style="visibility: hidden;" class="text-warning mt-3">Loading...</p>
                        </div>
                    </div>

                    <div class="modal-footer pt-0">
                        <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">Batal</button>
                        <button onclick="onAdd()" type="button" class="btn btn-primary mb-0">Simpan</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalCenterTitle">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header pb-1">
                        <div>
                            <h5>Hapus Usaha Berikut?</h5>
                        </div>
                    </div>
                    <div class="modal-body pt-0 mt-2" style="height: auto;">
                        <div class="row">
                            <div class="col-12">
                                <h4 id="delete-name"></h4>
                                <p style="font-size: 0.875rem;" id="delete-area"></p>
                            </div>
                        </div>
                        <input type="hidden" id="id-hidden" name="id-hidden">
                        <div>
                            <p id="loading-delete" class="text-warning mt-3">Loading...</p>
                        </div>
                    </div>

                    <div class="modal-footer pt-0">
                        <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">Batal</button>
                        <button onclick="onDelete()" type="button" class="btn btn-danger mb-0">Hapus</button>
                    </div>
                </div>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>

        <script>
            directories = [];

            const selectConfigs = [{
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
                {
                    selector: '#status',
                    placeholder: 'Pilih Status'
                },
                {
                    selector: '#level',
                    placeholder: 'Pilih Level'
                },
                {
                    selector: '#statusUpdate',
                    placeholder: 'Pilih Status'
                },
                {
                    selector: '#subdistrictUpdate',
                    placeholder: 'Pilih Kecamatan'
                },
                {
                    selector: '#villageUpdate',
                    placeholder: 'Pilih Desa'
                },
                {
                    selector: '#slsUpdate',
                    placeholder: 'Pilih SLS'
                },
                {
                    selector: '#subdistrictAdd',
                    placeholder: 'Pilih Kecamatan'
                },
                {
                    selector: '#villageAdd',
                    placeholder: 'Pilih Desa'
                },
                {
                    selector: '#slsAdd',
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
                '#subdistrict': () => {
                    pagination.reset();
                    emptyDirectoryList();
                    onAreaFilterChange()
                    loadVillage('', null, null);
                    renderView(null, null);
                },
                '#village': () => {
                    pagination.reset();
                    emptyDirectoryList();
                    onAreaFilterChange();
                    renderView(null, null);
                },
                '#status': () => {
                    pagination.reset();
                    renderView(null, null);
                },
                '#level': () => {
                    pagination.reset();
                    onLevelChange();
                },
                '#subdistrictUpdate': () => {
                    loadVillage('Update', null, null);
                },
                '#villageUpdate': () => {
                    loadSls('Update', null, null);
                },
                '#subdistrictAdd': () => {
                    loadVillage('Add', null, null);
                },
                '#villageAdd': () => {
                    loadSls('Add', null, null);
                },
                '#statusUpdate': () => {
                    updateInputStates(selectedBusiness);
                },
            };

            Object.entries(eventHandlers).forEach(([selector, handler]) => {
                $(selector).on('change', handler);
            });

            function filterDisabled(subdistrict_enable, village_enable, status_enable) {
                const elements = {
                    subdistrict: subdistrict_enable,
                    village: village_enable,
                    status: status_enable,
                    search: status_enable,
                };

                Object.entries(elements).forEach(([id, isDisabled]) => {
                    const element = document.getElementById(id);
                    if (element) element.disabled = isDisabled;
                });
            }

            function onLevelChange() {
                emptyDirectoryList()
                pagination.hide()

                let level = $('#level').val();
                let subdistrict = document.getElementById('subdistrict').value;
                let village = document.getElementById('village').value;

                // TODO
                document.getElementById("search").value = "";
                // $('#status').val(null).trigger('change');

                if (level === 'regency') {
                    filterDisabled(true, true, false);
                    renderView(null, null);
                } else if (level === 'subdistrict') {
                    filterDisabled(false, true, subdistrict == 0);
                    if (subdistrict != 0) {
                        renderView(null, null);
                    }
                } else if (level === 'village') {
                    filterDisabled(false, false, village == 0);
                    if (subdistrict != 0 && (level !== 'village' || village != 0)) {
                        renderView(null, null);
                    }
                }
            }

            var selectedBusiness = null;

            function openUpdateDirectoryModal(item) {
                selectedBusiness = item

                $('#updateDirectoryModal').modal('show');

                // document.getElementById('modaltitle').innerHTML = item.name + (item.owner ? ' (' + item.owner + ')' : '')

                document.getElementById('nameUpdate').value = item.name
                document.getElementById('ownerUpdate').value = item.owner

                const areaDetail = getLocationDetails(item)
                const details = [
                    areaDetail.subdistrict,
                    areaDetail.village,
                    areaDetail.sls
                ].filter(value => value).join(", ");

                const detailsArray = [
                    `[${areaDetail.long_code}] ${details}`,
                    item.initial_address && `Alamat awal: ${item.initial_address}`,
                    item.kbli && `KBLI: ${item.kbli}`,
                    item.category && `Kategori: ${item.category}`
                ].filter(Boolean);

                document.getElementById('modalsubtitle').innerHTML = detailsArray.join('<br>');

                document.getElementById('businessId').value = item.id

                document.getElementById('update-error').style.display = 'none'

                $('#statusUpdate').val(item.status.id).trigger('change');
                $('#subdistrictUpdate').val(null).trigger('change');
                $('#villageUpdate').val(null).trigger('change');
                $('#slsUpdate').val(null).trigger('change');

                updateInputStates(item)
            }

            function openAddModal() {
                $('#addDirectoryModal').modal('show');

                document.getElementById('nameAdd').value = null
                document.getElementById('ownerAdd').value = null
                document.getElementById('addressAdd').value = null
                document.getElementById('sourceAdd').value = null

                $('#subdistrictAdd').val(null).trigger('change');
                $('#villageAdd').val(null).trigger('change');
                $('#slsAdd').val(null).trigger('change');

                document.getElementById('add-error').style.display = 'none'
                document.getElementById('loading-add').style.display = 'none'

            }

            function onDeleteModal(itemString) {
                const item = JSON.parse(itemString.getAttribute('data-row'));
                event.stopPropagation()
                $('#deleteModal').modal('show');
                document.getElementById('loading-delete').style.visibility = 'hidden'

                $('#deleteModal').modal('show');
                document.getElementById('id-hidden').value = item.id;
                document.getElementById('delete-name').innerHTML = item.name;
                document.getElementById('delete-area').innerHTML = "[" + item.sls.long_code + "] " +
                    item.subdistrict.name + ", " + item.village.name + ", " + item.sls.name;
            }

            function updateInputStates(item) {
                const statusCol = document.getElementById("statusCol");
                const sourceCol = document.getElementById("sourceCol");
                const statusUpdate = document.getElementById("statusUpdate");
                const addressCol = document.getElementById("addressCol");
                const subdistrictCol = document.getElementById("subdistrictCol");
                const villageCol = document.getElementById("villageCol");
                const slsCol = document.getElementById("slsCol");

                document.getElementById('subdistrictUpdateLabel').innerHTML = ''
                document.getElementById('villageUpdateLabel').innerHTML = ''
                document.getElementById('slsUpdateLabel').innerHTML = ''
                document.getElementById('areaUpdateLabel').innerHTML = ''
                document.getElementById('slsColFiled').style.display = 'none'
                document.getElementById('switchArea').checked = false

                const isActive = statusUpdate.value === "2" || item.status.id == 90;
                const level = item.level;

                // Default all inputs to be hidden
                addressCol.style.display = "none";
                subdistrictCol.style.display = "none";
                villageCol.style.display = "none";
                slsCol.style.display = "none";
                statusCol.style.display = item.status.id === 90 ? "none" : "block";
                sourceCol.style.display = item.status.id != 90 ? "none" : "block";

                document.getElementById('addressUpdate').value = item.address

                if (isActive) {
                    if (item.sls != null) {
                        document.getElementById('subdistrictUpdateLabel').innerHTML = "[" + item.subdistrict.short_code + "] " +
                            item.subdistrict.name
                        document.getElementById('villageUpdateLabel').innerHTML = "[" + item.village.short_code + "] " + item
                            .village.name
                        document.getElementById('slsUpdateLabel').innerHTML = "[" + item.sls.short_code + "] " + item.sls.name
                        document.getElementById('areaUpdateLabel').innerHTML = 'Wilayah'
                        document.getElementById('slsColFiled').style.display = 'block'
                        addressCol.style.display = "block";

                    } else {
                        addressCol.style.display = "block";

                        subdistrictCol.style.display = "block";
                        villageCol.style.display = "block";
                        slsCol.style.display = "block";
                    }

                    if (item.status.id == 90) {
                        document.getElementById('sourceUpdate').value = item.source
                    } else {
                        document.getElementById('sourceUpdate').value = null
                    }
                }
            }

            function onChangeArea() {
                const statusUpdate = document.getElementById("statusUpdate");
                const subdistrictCol = document.getElementById("subdistrictCol");
                const villageCol = document.getElementById("villageCol");
                const slsCol = document.getElementById("slsCol");

                var isChecked = document.getElementById('switchArea').checked
                const level = selectedBusiness.level;

                if (isChecked) {
                    subdistrictCol.style.display = "block";
                    villageCol.style.display = "block";
                    slsCol.style.display = "block";

                } else {
                    // addressCol.style.display = "none";
                    subdistrictCol.style.display = "none";
                    villageCol.style.display = "none";
                    slsCol.style.display = "none";
                }
            }

            function emptyDirectoryList() {
                const resultDiv = document.getElementById('directorylist');
                resultDiv.innerHTML = '';

                pagination.reset()
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
                            if (group == '') {
                                $(villageSelector).append(
                                    `<option value="all">Semua Desa</option>`
                                );
                            }
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

            function loadSls(group = '', villageid = null, selectedsls = null) {

                let villageSelector = `#village${group}`;
                let slsSelector = `#sls${group}`;

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

            function onAreaFilterChange() {
                const level = document.getElementById('level').value;
                const id = (level === 'subdistrict') ? $('#subdistrict').val() : (level === 'village') ? $('#village').val() :
                    null;
                const isDisabled = (id == 0 || id == null);

                document.getElementById('status').disabled = isDisabled;
                document.getElementById('search').disabled = isDisabled;
            }

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

            function renderView(page, length) {

                filterUrl = ''
                filterTypes = ['level', 'subdistrict', 'village', 'status']
                filterTypes.forEach(f => {
                    filterUrl += getFilterUrl(f)
                });

                var pageUrl = '&start=0&length=10'
                if (page != null && length != null) {
                    pageUrl = `&start=${page * length}&length=${length}`
                }

                var searchUrl = ''
                var keyword = document.getElementById('search').value
                if (keyword != null && keyword != '') {
                    searchUrl = `&search%5Bvalue%5D=${keyword}`
                }

                // const resultDiv = document.getElementById('directorylist');
                // resultDiv.innerHTML = '<p class="text-warning">Loading<p/>';
                showLoading()

                $.ajax({
                    url: '/non-sls-directory/data?' + filterUrl + pageUrl + searchUrl,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        directories = []

                        const resultDiv = document.getElementById('directorylist');
                        resultDiv.innerHTML = '';

                        response.data.forEach(item => {
                            directories.push(item);

                            const itemDiv = document.createElement('div');
                            itemDiv.className = 'col-md-4 col-sm-6 col-xs-12 p-1';
                            itemDiv.style = "cursor: pointer;"

                            let button = ''
                            itemDiv.onclick = function() {
                                openUpdateDirectoryModal(item)
                            };
                            button = `
                                <button class="px-2 py-1 m-0 btn btn-icon btn-outline-primary btn-sm" type="button">
                                    <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
                                </button>
                            `

                            if (item.is_new) {
                                button = button + `<button data-row='${escapeJsonForHtml(JSON.stringify(item))}' onclick="onDeleteModal(this)" class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" type="button">
                                        <span class="btn-inner--icon"><i class="fas fa-trash-alt"></i></span>
                                    </button>`
                            }

                            const areaDetail = getLocationDetails(item)
                            const details = [
                                areaDetail.subdistrict,
                                areaDetail.village,
                                areaDetail.sls
                            ].filter(value => value).join(", ");

                            var owner = item.owner ? ` (${item.owner})` : ''

                            itemDiv.innerHTML = `
                        <div class="border d-flex justify-content-between align-items-center px-3 py-2 border-radius-md">
                            <div>
                                <p style="font-size: 0.875rem;" class="mb-1">${item.name}${owner}</p>
                                <p style="font-size: 0.7rem;" class="mb-2">Status: <span class="badge bg-gradient-${item.status.color}">${item.status.name}</span></p>
                                ${item.source ? `<p style="font-size: 0.7rem;" class="mb-0">Sumber: ${item.source}</p>` : ""}                               
                                ${item.sls_id ? `<p style="font-size: 0.7rem;" class="mb-0">[${areaDetail.long_code}] ${details}</p>` : ""}
                                ${item.last_modified_by ? `<p style="font-size: 0.7rem;" class="mb-0">Terakhir diupdate oleh: ${item.modified_by.firstname}</p>` : ""}
                            </div>
                            ${button}
                        </div>
                    `

                            resultDiv.appendChild(itemDiv);
                        });

                        if (response.data.length == 0) {
                            resultDiv.innerHTML = `<p class="text-small text-warning">No data</p>`
                        }

                        pagination.setTotalPages(Math.ceil(response.recordsFiltered / pagination.pageLength))
                        pagination.show()
                        hideLoading()
                    },
                    error: function(xhr, status, error) {
                        const resultDiv = document.getElementById('directorylist');
                        resultDiv.innerHTML = `
                        <div>
                            <p>
                                <span class="mr-2 text-sm text-danger">Gagal Menampilkan Sampel</span>    
                            </p>
                            <button onclick="refresh()" class="btn btn-sm btn-outline-primary">Muat Ulang</button>
                        </div>`;

                        hideLoading()
                        pagination.reset()
                        pagination.show()
                    }
                });
            }

            function getLocationDetails(row) {
                let long_code = row.regency.long_code;
                let subdistrict = row.subdistrict ? row.subdistrict.name : '';
                let village = row.village ? row.village.name : '';
                let sls = row.sls ? row.sls.name : '';

                if (row.subdistrict) long_code += row.subdistrict.short_code;
                if (row.village) long_code += row.village.short_code;
                if (row.sls) long_code += row.sls.short_code;

                return {
                    long_code,
                    subdistrict,
                    village,
                    sls
                };
            }

            function validate() {

                const statusUpdate = document.getElementById('statusUpdate')?.value;
                const addressUpdate = document.getElementById('addressUpdate')?.value;
                const slsUpdate = document.getElementById('slsUpdate')?.value;
                const switchChecked = document.getElementById('switchArea')?.checked || false;
                const updateError = document.getElementById('update-error');

                const nameUpdate = document.getElementById('nameUpdate')?.value;
                const ownerUpdate = document.getElementById('ownerUpdate')?.value;
                const sourceUpdate = document.getElementById('sourceUpdate')?.value;

                let nameValid = true
                let ownerValid = true
                let sourceValid = true
                let statusValid = true
                let addressValid = true

                if (!nameUpdate || nameUpdate.length < 4) {
                    nameValid = false;
                }

                if (selectedBusiness.status.id == 90) {
                    if (!ownerUpdate || ownerUpdate.length < 4) {
                        ownerValid = false;
                    }
                    if (!sourceUpdate || sourceUpdate.length < 4) {
                        sourceValid = false;
                    }
                } else {
                    if (!statusUpdate) {
                        statusValid = false;
                    }
                }

                if (statusUpdate == "2" || selectedBusiness.status.id == 90) {
                    if (!addressUpdate || addressUpdate.length < 4) {
                        addressValid = false;
                    }
                }

                let slsValid1 = true
                if (statusUpdate === '2' && (!slsUpdate || slsUpdate == '0')) {
                    if (selectedBusiness.sls_id != null && !switchChecked) {
                        slsValid1 = true
                    } else {
                        slsValid1 = false
                    }
                }

                let slsValid2 = true
                if (selectedBusiness.status.id == 90 && switchChecked && (!slsUpdate || slsUpdate == '0')) {
                    slsValid2 = false
                }

                let valid = statusValid && nameValid && ownerValid && sourceValid && addressValid && slsValid1 && slsValid2;
                updateError.style.display = valid ? 'none' : 'block';
                return valid;
            }

            function validateAdd() {
                let statusValid = true;
                const nameAdd = document.getElementById('nameAdd')?.value;
                const ownerAdd = document.getElementById('ownerAdd')?.value;
                const addressAdd = document.getElementById('addressAdd')?.value;
                const sourceAdd = document.getElementById('sourceAdd')?.value;

                const slsAdd = document.getElementById('slsAdd')?.value;
                const updateError = document.getElementById('add-error');

                if (!nameAdd || !ownerAdd || !addressAdd || !sourceAdd || !slsAdd || slsAdd == 0) {
                    statusValid = false;
                }

                updateError.style.display = statusValid ? 'none' : 'block';
                return statusValid;
            }

            function onSave() {
                document.getElementById('update-error').style.display = 'none'

                if (validate()) {
                    document.getElementById('loading-save').style.visibility = 'visible'

                    id = document.getElementById('businessId').value
                    var updateData = {
                        status: document.getElementById('statusUpdate').value,
                        subdistrict: document.getElementById('subdistrictUpdate').value,
                        village: document.getElementById('villageUpdate').value,
                        sls: document.getElementById('slsUpdate').value,
                        address: document.getElementById('addressUpdate').value,
                        switch: document.getElementById('switchArea').checked,
                        name: document.getElementById('nameUpdate').value,
                        owner: document.getElementById('ownerUpdate').value,
                        source: document.getElementById('sourceUpdate').value,
                    };

                    $.ajax({
                        url: `/directory/edit/non-sls/${id}`,
                        type: 'PATCH',
                        data: updateData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            $('#updateDirectoryModal').modal('hide');
                            document.getElementById('loading-save').style.visibility = 'hidden'

                            renderView(pagination.currentPage - 1, pagination.pageLength)
                        },
                        error: function(xhr, status, error) {
                            document.getElementById('loading-save').style.visibility = 'hidden'
                        }
                    });
                }
            }

            function onAdd() {
                document.getElementById('add-error').style.display = 'none'
                if (validateAdd()) {
                    var addData = {
                        name: document.getElementById('nameAdd').value,
                        owner: document.getElementById('ownerAdd').value,
                        address: document.getElementById('addressAdd').value,
                        source: document.getElementById('sourceAdd').value,
                        sls: document.getElementById('slsAdd').value,
                    };
                    $.ajax({
                        url: `/non-sls-directory`,
                        type: 'POST',
                        data: addData,
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            $('#addDirectoryModal').modal('hide');
                            document.getElementById('loading-add').style.visibility = 'hidden'

                            // renderTable()
                            renderView(pagination.currentPage - 1, pagination.pageLength)
                        },
                        error: function(xhr, status, error) {
                            document.getElementById('loading-add').style.visibility = 'hidden'
                        }
                    });
                }
            }

            function onDelete() {
                document.getElementById('loading-delete').style.visibility = 'visible'

                $.ajax({
                    url: `/non-sls-directory/${document.getElementById('id-hidden').value}`,
                    type: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        renderView(pagination.currentPage - 1, pagination.pageLength)
                        $('#deleteModal').modal('hide');
                        document.getElementById('loading-delete').style.visibility = 'hidden'
                    },
                    error: function(xhr, status, error) {
                        document.getElementById('loading-delete').innerHTML = 'Gagal menghapus usaha'
                    }
                });
            }

            function debounce(func, delay) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), delay);
                };
            }

            function searchByKeyword(query) {
                renderView(pagination.currentPage - 1, pagination.pageLength)
            }

            const searchInput = document.getElementById("search");
            searchInput.addEventListener("input", debounce((event) => {
                searchByKeyword(event.target.value);
            }, 500));

            function refresh() {
                renderView(pagination.currentPage - 1, pagination.pageLength)
            }
        </script>

        <script>
            class Pagination {
                constructor(options = {}) {
                    this.currentPage = options.currentPage || 1;
                    this.totalPages = options.totalPages || 1;
                    this.visiblePages = options.visiblePages || 5;
                    this.pageLength = options.pageLength || 10;
                    this.container = options.container || document.getElementById('paginationContainer');
                    this.onPageChange = options.onPageChange || (() => {});
                    this.pageLengthOptions = options.pageLengthOptions || [5, 10, 25, 50, 100];

                    // Track visibility state
                    this.isVisible = true;

                    this.init();
                }

                init() {
                    this.render();
                    this.attachEventListeners();
                }

                createPageLengthControl() {
                    const control = document.createElement('div');
                    control.className = 'page-length-control d-flex align-items-center';

                    const label = document.createElement('label');
                    label.textContent = 'Per page:';
                    label.className = 'me-2';

                    const select = document.createElement('select');
                    select.className = 'form-select form-select-sm';

                    this.pageLengthOptions.forEach(value => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = value;
                        option.selected = value === this.pageLength;
                        select.appendChild(option);
                    });

                    select.addEventListener('change', (e) => {
                        this.setPageLength(parseInt(e.target.value));
                    });

                    control.appendChild(label);
                    control.appendChild(select);
                    return control;
                }

                render() {
                    this.container.innerHTML = '';
                    const wrapper = document.createElement('div');
                    wrapper.className = `pagination-wrapper ${this.isVisible ? '' : 'hidden'}`;

                    const paginationContainer = document.createElement('nav');
                    paginationContainer.setAttribute('aria-label', 'Page navigation');

                    const pagination = document.createElement('ul');
                    pagination.className = 'pagination mb-0';

                    // Start button
                    pagination.appendChild(this.createPageItem('«', 1, this.currentPage === 1));

                    // Previous button
                    pagination.appendChild(this.createPageItem('‹', this.currentPage - 1, this.currentPage === 1));

                    // Generate page numbers with intelligent ellipsis
                    const pageNumbers = this.generatePageNumbers();
                    pageNumbers.forEach(page => {
                        if (page === '...') {
                            pagination.appendChild(this.createEllipsisItem());
                        } else {
                            pagination.appendChild(this.createPageItem(page, page, false, page === this
                                .currentPage));
                        }
                    });

                    // Next button
                    pagination.appendChild(this.createPageItem('›', this.currentPage + 1, this.currentPage === this
                        .totalPages));

                    // End button
                    pagination.appendChild(this.createPageItem('»', this.totalPages, this.currentPage === this.totalPages));

                    paginationContainer.appendChild(pagination);
                    wrapper.appendChild(paginationContainer);
                    wrapper.appendChild(this.createPageLengthControl());
                    this.container.appendChild(wrapper);
                }

                generatePageNumbers() {
                    const totalPages = this.totalPages;
                    const currentPage = this.currentPage;
                    const visiblePages = this.visiblePages;

                    // If total pages is less than or equal to visible pages, show all pages
                    if (totalPages <= visiblePages + 2) {
                        return Array.from({
                            length: totalPages
                        }, (_, i) => i + 1);
                    }

                    const pages = [];
                    const leftBuffer = Math.floor((visiblePages - 1) / 2);
                    const rightBuffer = Math.ceil((visiblePages - 1) / 2);

                    // Always show first page
                    pages.push(1);

                    // Determine start and end of middle range
                    let start = Math.max(2, currentPage - leftBuffer);
                    let end = Math.min(totalPages - 1, currentPage + rightBuffer);

                    // Adjust start and end to maintain consistent number of visible pages
                    if (currentPage <= leftBuffer + 1) {
                        end = Math.min(visiblePages, totalPages - 1);
                    }
                    if (currentPage >= totalPages - rightBuffer) {
                        start = Math.max(2, totalPages - visiblePages);
                    }

                    // Add first ellipsis if needed
                    if (start > 2) {
                        pages.push('...');
                    }

                    // Add middle range of pages
                    for (let i = start; i <= end; i++) {
                        pages.push(i);
                    }

                    // Add second ellipsis if needed
                    if (end < totalPages - 1) {
                        pages.push('...');
                    }

                    // Always show last page
                    pages.push(totalPages);

                    return pages;
                }

                createPageItem(text, pageNumber, disabled = false, active = false) {
                    const li = document.createElement('li');
                    li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;

                    const a = document.createElement('a');
                    a.className = 'page-link';
                    a.href = '#';
                    a.textContent = text;
                    a.dataset.page = pageNumber;

                    li.appendChild(a);
                    return li;
                }

                createEllipsisItem() {
                    const li = document.createElement('li');
                    li.className = 'page-item ellipsis';

                    const span = document.createElement('span');
                    span.className = 'page-link';
                    span.textContent = '...';

                    li.appendChild(span);
                    return li;
                }

                attachEventListeners() {
                    this.container.addEventListener('click', (e) => {
                        const pageLink = e.target.closest('.page-link');
                        if (!pageLink) return;

                        e.preventDefault();
                        const pageItem = pageLink.closest('.page-item');
                        if (pageItem.classList.contains('disabled') || pageItem.classList.contains('active') ||
                            pageItem.classList.contains('ellipsis')) return;

                        const newPage = parseInt(pageLink.dataset.page);
                        this.setCurrentPage(newPage);
                    });
                }

                setCurrentPage(page) {
                    if (page < 1 || page > this.totalPages) return;
                    this.currentPage = page;
                    this.render();
                    this.onPageChange(page, this.pageLength);
                }

                setVisiblePages(count) {
                    this.visiblePages = count;
                    this.render();
                }

                setPageLength(length) {
                    this.pageLength = length;
                    this.currentPage = 1; // Reset to first page when changing page length
                    this.render();
                    this.onPageChange(this.currentPage, this.pageLength);
                }

                setTotalPages(total) {
                    this.totalPages = total;
                    if (this.currentPage > this.totalPages) {
                        this.currentPage = this.totalPages;
                    }
                    this.render();
                }

                reset() {
                    this.currentPage = 1
                    this.totalPages = 1
                }

                // New method to toggle visibility
                toggle() {
                    this.isVisible = !this.isVisible;
                    this.render();
                    return this.isVisible;
                }

                // Method to show pagination
                show() {
                    this.isVisible = true;
                    this.render();
                }

                // Method to hide pagination
                hide() {
                    this.isVisible = false;
                    this.render();
                }

                // Method to check if pagination is visible
                isCurrentlyVisible() {
                    return this.isVisible;
                }
            }

            // Example usage:
            const pagination = new Pagination({
                currentPage: 1,
                totalPages: 1,
                visiblePages: 5,
                pageLength: 10,
                pageLengthOptions: [10, 25, 50],
                container: document.getElementById('paginationContainer'),
                onPageChange: (page, pageLength) => {
                    // console.log(`Page changed to ${page}, items per page: ${pageLength}`);
                    renderView((page - 1), pageLength)
                }
            });

            // Function to show loading overlay
            function showLoading() {
                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.style.display = 'flex';
            }

            // Function to hide loading overlay
            function hideLoading() {
                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.style.display = 'none';
            }

            pagination.hide()

            function escapeJsonForHtml(jsonString) {
                return jsonString
                    .replace(/"/g, '&quot;') // Escape double quotes
                    .replace(/'/g, '&#39;'); // Escape single quotes
            }
        </script>
    @endpush
@endsection
