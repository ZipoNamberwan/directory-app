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
                <div class="col-md-4">
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
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                    <select disabled id="village" name="village" class="form-control" data-toggle="select" name="village"></select>
                </div>
                <!-- <div class="col-md-4">
                    <label class="form-control-label">Status <span class="text-danger">*</span></label>
                    <select disabled style="width: 100%;" id="status" name="status" class="form-control" data-toggle="select">
                        <option value="0" disabled selected> -- Pilih Status -- </option>
                        @foreach ($statuses as $status)
                        <option value="{{ $status->id }}" {{ old('status') == $status->id ? 'selected' : '' }}>
                            {{ $status->name }}
                        </option>
                        @endforeach
                    </select>
                </div> -->
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
            <div id="directorylist" class="row">

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
                    <label class="form-control-label">Status <span class="text-danger">*</span></label>
                    <select id="status" name="status" class="form-control" data-toggle="select" required>
                        <option value="0" disabled selected> -- Pilih Status -- </option>
                        @foreach($statuses as $status)
                        <option value="{{$status->id}}">{{$status->name}}</option>
                        @endforeach
                    </select>
                    <div id="status_error" style="display: none;" class="text-valid mt-2">
                        Belum diisi
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
    statuses = @json($statuses);
</script>

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
    ].forEach(config => {
        $(config.selector).select2({
            placeholder: config.placeholder,
            allowClear: true,
        });
    });

    $('#subdistrict').on('change', function() {
        loadVillage(null, null);
        renderView();
    });
    $('#village').on('change', function() {
        renderView();
    });
    // $('#status').on('change', function() {
    //     renderView();
    // });
    $('#level').on('change', function() {
        onLevelChange()
    });

    function areaDisabled(subdistrict_enable, village_enable) {
        let subdistrict = document.getElementById('subdistrict')
        let village = document.getElementById('village')

        subdistrict.disabled = subdistrict_enable;
        village.disabled = village_enable;
    }

    function onLevelChange() {
        let level = $('#level').val();
        let subdistrict = document.getElementById('subdistrict').value;
        let village = document.getElementById('village').value;

        if (level === 'regency') {
            areaDisabled(true, true);
            renderView();
        } else {
            areaDisabled(false, level === 'subdistrict');

            if (subdistrict != 0 && (level !== 'village' || village != 0)) {
                renderView();
            }
        }
    }

    function openUpdateDirectoryModal(item) {
        const areaDetail = getLocationDetails(item)
        $('#updateDirectoryModal').modal('show');

        document.getElementById('modaltitle').innerHTML = item.name
        document.getElementById('modalsubtitle').innerHTML = "[" + areaDetail.long_code + "] " +
            areaDetail.subdistrict + ", " + areaDetail.village
        document.getElementById('business_id').value = item.id

        document.getElementById('status_error').style.display = 'none'

        $('#status').empty();
        $('#status').append(`<option value="0" disabled> -- Pilih Status -- </option>`);
        statuses.forEach((st) => {
            var sel = st.id == item.status.id ? 'selected' : ''
            if (st.name != 'Baru')
                $('#status').append(`<option ${sel} value="${st.id}">${st.name}</option>`);
        })
    }

    function emptyDirectoryList() {
        const resultDiv = document.getElementById('directorylist');
        resultDiv.innerHTML = '';
    }

    function loadVillage(subdistrictid = null, selectedvillage = null) {
        emptyDirectoryList()
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
                    $('#village').append(`<option value="0" disabled selected> -- Pilih Desa -- </option>`);
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
            $('#village').append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
        }
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

    function renderView() {
        emptyDirectoryList()

        filterUrl = ''
        filterTypes = ['level', 'subdistrict', 'village' /* , 'status' */ ]
        filterTypes.forEach(f => {
            filterUrl += getFilterUrl(f)
        });

        const resultDiv = document.getElementById('directorylist');
        resultDiv.innerHTML = '<p class="text-warning">Loading<p/>';

        $.ajax({
            url: '/non-sls-directory/data?' + filterUrl,
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
                    resultDiv.innerHTML = `<p class="text-small text-warning">Tidak ada direktori di SLS ini</p>`
                }
            },
            error: function(xhr, status, error) {
                const resultDiv = document.getElementById('directorylist');
                resultDiv.innerHTML = `
                        <div class="d-flex">
                            <span class="mr-2">Gagal Menampilkan Sampel</span>
                            <button onclick="loadSample(null)" class="btn btn-sm btn-outline-primary">Muat Ulang</button>
                        </div>`;
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
        if (document.getElementById('status').value == 0 || document.getElementById('status').value == null) {
            status_valid = false
            document.getElementById('status_error').style.display = 'block'
        } else {
            document.getElementById('status_error').style.display = 'none'
        }

        return status_valid
    }

    function onSave() {
        document.getElementById('status_error').style.visibility = 'hidden'

        if (validate()) {
            document.getElementById('loading-save').style.visibility = 'visible'

            id = document.getElementById('business_id').value
            var updateData = {
                status: document.getElementById('status').value,
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

                    renderView()
                },
                error: function(xhr, status, error) {
                    document.getElementById('loading-save').style.visibility = 'hidden'
                }
            });
        }
    }
</script>
@endpush

@endsection