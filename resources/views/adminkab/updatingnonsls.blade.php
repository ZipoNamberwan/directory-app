@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
<link href="/assets/css/app.css" rel="stylesheet" />
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
<link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
<link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<link href="/vendor/fontawesome/css/all.min.css" rel="stylesheet">

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
                        @if ($status->name != 'Baru')
                        <option value="{{ $status->id }}" {{ old('status') == $status->id ? 'selected' : '' }}>
                            [{{$status->code}}] {{ $status->name }}
                        </option>
                        @endif
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
            <table id="myTable" class="align-items-center mb-0 text-sm">
                <thead>
                    <tr>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Usaha</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Wilayah</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Status</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Terakhir Diupdate Oleh</th>
                        <th class="text-uppercase text-small font-weight-bolder opacity-7">Aksi</th>
                    </tr>
                </thead>
                <tbody>

                </tbody>
            </table>
            <div id="directorylist" class="row mb-3">

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
                                <option value="{{$status->id}}">{{$status->name}}</option>
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
                                <input onchange="onChangeArea()" class="form-check-input" type="checkbox" role="switch"
                                    name="switchArea" id="switchArea">
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
            emptyDirectoryList();
            onAreaFilterChange()
            loadVillage('', null, null);
            renderTable();
        },
        '#village': () => {
            emptyDirectoryList();
            onAreaFilterChange();
            renderTable();
        },
        '#status': () => {
            renderTable();
        },
        '#level': () => {
            onLevelChange();
        },
        '#subdistrictUpdate': () => {
            loadVillage('Update', null, null);
        },
        '#villageUpdate': () => {
            loadSls('Update', null, null);
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

        let level = $('#level').val();
        let subdistrict = document.getElementById('subdistrict').value;
        let village = document.getElementById('village').value;

        // TODO
        document.getElementById("search").value = "";
        // $('#status').val(null).trigger('change');

        if (level === 'regency') {
            filterDisabled(true, true, false);
            renderTable();
        } else if (level === 'subdistrict') {
            filterDisabled(false, true, subdistrict == 0);
            if (subdistrict != 0) {
                renderTable();
            }
        } else if (level === 'village') {
            filterDisabled(false, false, village == 0);
            if (subdistrict != 0 && (level !== 'village' || village != 0)) {
                renderTable();
            }
        }
    }

    var selectedBusiness = null;

    function openUpdateDirectoryModal(itemString) {
        const item = JSON.parse(itemString.getAttribute('data-row'));
        selectedBusiness = item

        $('#updateDirectoryModal').modal('show');

        document.getElementById('modaltitle').innerHTML = item.name

        const areaDetail = getLocationDetails(item)
        const details = [
            areaDetail.subdistrict,
            areaDetail.village,
            areaDetail.sls
        ].filter(value => value).join(", ");

        document.getElementById('modalsubtitle').innerHTML = `[${areaDetail.long_code}] ${details}`;

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

    function onChangeArea() {
        const statusCol = document.getElementById("statusUpdate");
        const subdistrictCol = document.getElementById("subdistrictCol");
        const villageCol = document.getElementById("villageCol");
        const slsCol = document.getElementById("slsCol");

        var isChecked = document.getElementById('switchArea').checked
        const level = selectedBusiness.level;

        if (isChecked) {
            if (level === "regency") {
                subdistrictCol.style.display = "block";
                villageCol.style.display = "block";
                slsCol.style.display = "block";
            } else if (level === "subdistrict") {
                villageCol.style.display = "block";
                slsCol.style.display = "block";
                loadVillage('Update', selectedBusiness.subdistrict_id, null)
            } else if (level === "village") {
                slsCol.style.display = "block";
                loadSls('Update', selectedBusiness.village_id, null)
            }
        } else {
            addressCol.style.display = "none";
            villageCol.style.display = "none";
            slsCol.style.display = "none";
        }
    }

    function emptyDirectoryList() {
        const resultDiv = document.getElementById('directorylist');
        resultDiv.innerHTML = '';
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

    function onAreaFilterChange() {
        const level = document.getElementById('level').value;
        const id = (level === 'subdistrict') ? $('#subdistrict').val() : (level === 'village') ? $('#village').val() : null;
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

    function renderTable() {
        filterUrl = ''
        filterTypes = ['level', 'subdistrict', 'village', 'status']
        filterTypes.forEach(f => {
            filterUrl += getFilterUrl(f)
        });

        table.ajax.url('/non-sls-directory/data?' + filterUrl).load();
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
        let status_valid = true;
        const statusUpdate = document.getElementById('statusUpdate')?.value;
        const addressUpdate = document.getElementById('addressUpdate')?.value;
        const slsUpdate = document.getElementById('slsUpdate')?.value;
        const switchChecked = document.getElementById('switchArea')?.checked || false;
        const updateError = document.getElementById('update-error');

        if (!statusUpdate || statusUpdate == 0) {
            status_valid = false;
        } else if (statusUpdate == "2" && (!addressUpdate || addressUpdate == 0 || !slsUpdate || slsUpdate == 0)) {
            if (selectedBusiness.sls_id !== null && !switchChecked) {
                return true; // No need to show error in this case
            }
            status_valid = false;
        }

        updateError.style.display = status_valid ? 'none' : 'block';
        return status_valid;
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
                switch: document.getElementById('switchArea').checked,
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

                    renderTable()
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
        renderTable()
    }

    const searchInput = document.getElementById("search");
    searchInput.addEventListener("input", debounce((event) => {
        searchByKeyword(event.target.value);
    }, 500));


    let table = new DataTable('#myTable', {
        order: [],
        serverSide: true,
        processing: true,
        deferLoading: 0,
        ajax: {
            url: '/non-sls-directory/data',
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
                    var areaDetail = getLocationDetails(row)

                    if (type === 'display') {
                        return `<div class="my-1"> 
                        <p style='font-size: 0.7rem' class='text-secondary mb-0'>${areaDetail.long_code}</p>
                        <p style='font-size: 0.7rem' class='text-secondary mb-0'>${areaDetail.subdistrict}</p>
                        <p style='font-size: 0.7rem' class='text-secondary mb-0'>${areaDetail.village}</p>
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
                data: "modified_by",
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
            {
                responsivePriority: 4,
                width: "10%",
                data: "id",
                type: "text",
                render: function(data, type, row) {
                    if (type === 'display') {
                        return `<button data-row='${JSON.stringify(row)}' onclick="openUpdateDirectoryModal(this)" class="px-2 py-1 m-0 btn btn-icon btn-outline-primary btn-sm" type="button">
                                    <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
                                </button>`
                    }
                    return data;
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

@endsection