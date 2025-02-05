@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
<link href="/assets/css/app.css" rel="stylesheet" />
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
<link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
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
                    <select style="width: 100%;" id="level" name="level" class="form-control" data-toggle="select">
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
                    <select disabled style="width: 100%;" id="subdistrict" name="subdistrict" class="form-control" data-toggle="select">
                        <option value="0" disabled selected> -- Pilih Kecamatan -- </option>
                        @foreach ($subdistricts as $subdistrict)
                        <option value="{{ $subdistrict->id }}" {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                            [{{ $subdistrict->short_code}}] {{ $subdistrict->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                    <select disabled id="village" name="village" class="form-control" data-toggle="select" name="village"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-control-label">Status <span class="text-danger">*</span></label>
                    <select disabled style="width: 100%;" id="status" name="status" class="form-control" data-toggle="select">
                        <option value="0" disabled selected> -- Pilih Status -- </option>
                        @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" {{ old('status') == $status->id ? 'selected' : '' }}>
                            [{{$status->code}}] {{ $status->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-control-label" for="search">Nama <span class="text-danger">*</span></label>
                    <input disabled type="text" name="search" class="form-control mb-0" id="search" placeholder="Cari...">
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-2">
        <div class="card-header pb-0">
            <div class="d-flex align-items-center">
                <p class="mb-0">Daftar Direktori Tidak Sampai Level SLS</p>
                <!-- <button id="add-button" onclick="openAddModal()" class="btn btn-primary btn-sm ms-auto p-2 m-0">Tambah</button> -->
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

    <div class="modal fade" id="updateDirectoryModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header pb-1">
                    <div>
                        <h5 id="modaltitle">Modal title</h5>
                        <span class="mb-0" style="font-size: 0.75rem;" id="modalsubtitle">Modal title</span>
                    </div>
                    <!-- <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button> -->
                </div>
                <input type="hidden" id="business_id" />
                <div class="modal-body pt-0 mt-2" style="height: auto;">
                    <div class="row">
                        <div class="col-12">
                            <label class="form-control-label">Status <span class="text-danger">*</span></label>
                            <select id="statusUpdate" name="status" class="form-control" data-toggle="select" required>
                                <option value="0" disabled selected> -- Pilih Status -- </option>
                                @foreach($statuses as $status)
                                @if ($status->name != 'Baru')
                                <option value="{{$status->id}}">[{{$status->code}}] {{$status->name}}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div id="addressCol" class="col-12">
                            <label class="form-control-label" for="addressUpdate">Alamat Lengkap <span class="text-danger">*</span></label>
                            <input type="text" name="addressUpdate" class="form-control mb-0" id="addressUpdate" placeholder="Alamat Lengkap">
                        </div>
                        <div id="slsColFiled" class="col-12 my-2">
                            <label class="form-control-label" id="areaUpdateLabel"></label>
                            <p class="mb-1 text-sm text-muted" id="subdistrictUpdateLabel"></p>
                            <p class="mb-1 text-sm text-muted" id="villageUpdateLabel"></p>
                            <p class="mb-1 text-sm text-muted" id="slsUpdateLabel"></p>
                            <div id="switchAreaLabel" class="form-check form-switch mt-2">
                                <input onchange="" class="form-check-input" type="checkbox" role="switch" id="switchArea">
                                <label class="form-check-label" for="flexSwitchCheckDefault">Ganti Wilayah</label>
                            </div>
                        </div>
                        <div id="subdistrictCol" class="col-12">
                            <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                            <select id="subdistrictUpdate" name="subdistrictUpdate" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Kecamatan -- </option>
                                @foreach ($subdistricts as $subdistrict)
                                <option value="{{ $subdistrict->id }}" {{ old('subdistrict') == $subdistrict->id ? 'selected' : '' }}>
                                    [{{ $subdistrict->short_code}}] {{ $subdistrict->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div id="villageCol" class="col-12">
                            <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                            <select id="villageUpdate" name="villageUpdate" class="form-control" data-toggle="select" name="village"></select>
                        </div>
                        <div id="slsCol" class="col-12">
                            <label class="form-control-label">SLS <span class="text-danger">*</span></label>
                            <select id="slsUpdate" name="slsUpdate" class="form-control" data-toggle="select"></select>
                        </div>
                    </div>

                    <div id="update-error">
                        <p class="error-feedback mb-0 mt-2">
                            Ada yang Belum diisi
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
    @include('layouts.footers.auth.footer')
</div>

@push('js')
<script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
<script src="/vendor/select2/select2.min.js"></script>
<script src="/vendor/datatables/dataTables.min.js"></script>
<script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

<script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
<script src="/vendor/datatables/dataTables.responsive.min.js"></script>

<!-- <script>
    statuses = @json($statuses);
</script> -->

<script>
    directories = [];

    [{
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
    ].forEach(config => {
        $(config.selector).select2({
            placeholder: config.placeholder,
            allowClear: true,
        });
    });

    $('#subdistrict').on('change', function() {
        pagination.reset()
        emptyDirectoryList();
        loadVillage('', null, null);
        renderView(null, null);
    });
    $('#village').on('change', function() {
        emptyDirectoryList();
        pagination.reset()
        onVillageChange()
        renderView(null, null);
    });
    $('#status').on('change', function() {
        pagination.reset()
        renderView(null, null);
    });
    $('#level').on('change', function() {
        pagination.reset()
        onLevelChange()
    });

    $('#subdistrictUpdate').on('change', function() {
        loadVillage('Update', null, null);
    });
    $('#villageUpdate').on('change', function() {
        loadSls('Update', null, null)
    });
    $('#statusUpdate').on('change', function() {
        updateInputStates(selectedBusiness)
    });

    function filterDisabled(subdistrict_enable, village_enable, status_enable) {
        let subdistrict = document.getElementById('subdistrict')
        let village = document.getElementById('village')
        let status = document.getElementById('status')
        let search = document.getElementById('search')

        subdistrict.disabled = subdistrict_enable;
        village.disabled = village_enable;
        status.disabled = status_enable;
        search.disabled = status_enable;
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

        const areaDetail = getLocationDetails(item)
        $('#updateDirectoryModal').modal('show');

        document.getElementById('modaltitle').innerHTML = item.name
        document.getElementById('modalsubtitle').innerHTML = "[" + areaDetail.long_code + "] " +
            areaDetail.subdistrict + ", " + areaDetail.village
        document.getElementById('business_id').value = item.id

        document.getElementById('update-error').style.display = 'none'

        $('#statusUpdate').val(item.status.id).trigger('change');
        $('#subdistrictUpdate').val(null).trigger('change');
        $('#villageUpdate').val(null).trigger('change');
        $('#slsUpdate').val(null).trigger('change');

        updateInputStates(item)
    }

    function updateInputStates(item) {
        const statusCol = document.getElementById("statusUpdate");
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

        const isActive = statusCol.value === "2";
        const level = item.level;

        // Default all inputs to be hidden
        addressCol.style.display = "none";
        subdistrictCol.style.display = "none";
        villageCol.style.display = "none";
        slsCol.style.display = "none";

        document.getElementById('addressUpdate').value = item.address

        if (isActive) {
            if (item.sls != null) {
                document.getElementById('subdistrictUpdateLabel').innerHTML = "[" + item.subdistrict.short_code + "] " + item.subdistrict.name
                document.getElementById('villageUpdateLabel').innerHTML = "[" + item.village.short_code + "] " + item.village.name
                document.getElementById('slsUpdateLabel').innerHTML = "[" + item.sls.short_code + "] " + item.sls.name
                document.getElementById('areaUpdateLabel').innerHTML = 'Wilayah'
                document.getElementById('slsColFiled').style.display = 'block'
            } else {
                addressCol.style.display = "block";

                if (level === "regency") {
                    subdistrictCol.style.display = "block";
                    villageCol.style.display = "block";
                    slsCol.style.display = "block";
                } else if (level === "subdistrict") {
                    villageCol.style.display = "block";
                    slsCol.style.display = "block";
                    loadVillage('Update', item.subdistrict_id, null)
                } else if (level === "village") {
                    slsCol.style.display = "block";
                    loadSls('Update', item.village_id, null)
                }
            }
        }
    }

    function onChangeArea(){

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
                    $(villageSelector).empty().append(`<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                    $(slsSelector).empty().append(`<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                    response.forEach(element => {
                        let selected = selectedvillage == String(element.id) ? 'selected' : '';
                        $(villageSelector).append(`<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`);
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
                    $(slsSelector).empty().append(`<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                    response.forEach(element => {
                        let selected = selectedsls == String(element.id) ? 'selected' : '';
                        $(slsSelector).append(`<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`);
                    });
                }
            });
        } else {
            $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
        }
    }

    function onVillageChange() {
        let id = $('#village').val();
        document.getElementById('status').disabled = (id == 0 || id == null);
        document.getElementById('search').disabled = (id == 0 || id == null);
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
                    if (item.status.id != 4) {
                        itemDiv.onclick = function() {
                            openUpdateDirectoryModal(item)
                        };
                        button = `
                                <button class="px-2 py-1 m-0 btn btn-icon btn-outline-primary btn-sm" type="button">
                                    <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
                                </button>
                            `
                    } else {
                        itemDiv.onclick = function() {
                            openUpdateNewModal(item)
                        };
                        button = `
                            <button onclick="onDeleteModal(${JSON.stringify(item).replace(/"/g, '&quot;')})" class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" type="button">
                                <span class="btn-inner--icon"><i class="fas fa-trash-alt"></i></span>
                            </button>
                        `
                    }

                    itemDiv.innerHTML = `
                        <div class="border d-flex justify-content-between align-items-center px-3 py-2 border-radius-md">
                            <div>
                                <p style="font-size: 0.875rem;" class="mb-1">${item.name}</p>
                                <p style="font-size: 0.7rem;" class="mb-0">Status: <span class="badge bg-gradient-${item.status.color}">${item.status.name}</span></p>
                            </div>
                            ${button}
                        </div>
                    `

                    resultDiv.appendChild(itemDiv);
                });

                if (response.data.length == 0) {
                    resultDiv.innerHTML = `<p class="text-small text-warning">No data</p>`
                }

                pagination.setTotalPages(Math.floor(response.recordsFiltered / pagination.pageLength))
                pagination.show()
                hideLoading()
            },
            error: function(xhr, status, error) {
                const resultDiv = document.getElementById('directorylist');
                resultDiv.innerHTML = `
                        <div class="d-flex">
                            <span class="mr-2">Gagal Menampilkan Sampel</span>
                            <button onclick="loadSample(null)" class="btn btn-sm btn-outline-primary">Muat Ulang</button>
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

        if (row.subdistrict) long_code += row.subdistrict.short_code;
        if (row.village) long_code += row.village.short_code;

        return {
            long_code,
            subdistrict,
            village
        };
    }

    function validate() {
        var status_valid = true
        if (document.getElementById('statusUpdate').value == 0 ||
            document.getElementById('statusUpdate').value == null
        ) {
            status_valid = false
            document.getElementById('update-error').style.display = 'block'
        } else {
            if (document.getElementById('statusUpdate').value == "2" &&
                (document.getElementById('addressUpdate').value == 0 ||
                    document.getElementById('addressUpdate').value == null ||
                    document.getElementById('slsUpdate').value == 0 ||
                    document.getElementById('slsUpdate').value == null)) {
                status_valid = false
                document.getElementById('update-error').style.display = 'block'
            } else {
                document.getElementById('update-error').style.display = 'none'
            }
        }

        return status_valid
    }

    function onSave() {
        document.getElementById('update-error').style.display = 'none'

        if (validate()) {
            document.getElementById('loading-save').style.visibility = 'visible'

            id = document.getElementById('business_id').value
            var updateData = {
                status: document.getElementById('statusUpdate').value,
                subdistrict: document.getElementById('subdistrictUpdate').value,
                village: document.getElementById('villageUpdate').value,
                sls: document.getElementById('slsUpdate').value,
                address: document.getElementById('addressUpdate').value,
                new: false
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

    function debounce(func, delay) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), delay);
        };
    }

    function searchByKeyword(query) {
        renderView(pagination.currentPage, pagination.pageLength)
    }

    const searchInput = document.getElementById("search");
    searchInput.addEventListener("input", debounce((event) => {
        searchByKeyword(event.target.value);
    }, 500));
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
                    pagination.appendChild(this.createPageItem(page, page, false, page === this.currentPage));
                }
            });

            // Next button
            pagination.appendChild(this.createPageItem('›', this.currentPage + 1, this.currentPage === this.totalPages));

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
                if (pageItem.classList.contains('disabled') || pageItem.classList.contains('active') || pageItem.classList.contains('ellipsis')) return;

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
</script>
@endpush

@endsection