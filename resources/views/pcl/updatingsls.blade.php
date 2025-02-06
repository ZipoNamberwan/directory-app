@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
<link href="/assets/css/app.css" rel="stylesheet" />
<link href="/vendor/select2/select2.min.css" rel="stylesheet" />
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
            <div class="row">
                <div class="col-md-4">
                    <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                    <select style="width: 100%;" id="subdistrict" name="subdistrict" class="form-control" data-toggle="select">
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
                    <select id="village" name="village" class="form-control" data-toggle="select" name="village"></select>
                </div>
                <div id="sls_div" class="col-md-4">
                    <label class="form-control-label">SLS <span class="text-danger">*</span></label>
                    <select id="sls" name="sls" class="form-control" data-toggle="select"></select>
                </div>
            </div>
        </div>
    </div>
    <div class="card mt-2">
        <div class="card-header pb-0">
            <div class="d-flex align-items-center">
                <p class="mb-0">Daftar Direktori Sampai Level SLS</p>
                <button id="add-button" onclick="openAddModal()" class="btn btn-primary btn-sm ms-auto p-2 m-0">Tambah</button>
            </div>
        </div>
        <div class="card-body">
            <div id="directorylist" class="row"></div>
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

    <div class="modal fade" id="updateNewModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header pb-1">
                    <div>
                        <h5 id="modaltitle-new">Modal title</h5>
                        <span class="mb-0" style="font-size: 0.75rem;" id="modalsubtitle-new">Modal title</span>
                    </div>
                </div>
                <input type="hidden" id="business_id_new" />
                <div class="modal-body pt-0 mt-2" style="height: auto;">
                    <label class="form-control-label">Nama Usaha <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" placeholder="Nama Usaha" id="name-new" name="name-new">
                    <p id="name-new-error" style="display: none; font-size: 0.6rem; color:red"></p>

                    <div id="status_error_new" style="display: none;" class="text-valid mt-2">
                        Belum diisi
                    </div>
                    <div>
                        <p id="loading-save-new" style="visibility: hidden;" class="text-warning mt-3">Loading...</p>
                    </div>
                </div>

                <div class="modal-footer pt-0">
                    <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">Batal</button>
                    <button onclick="onSaveNew()" type="button" class="btn btn-primary mb-0">Simpan</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header pb-1">
                    <div>
                        <h5>Tambah Usaha Baru</h5>
                        <span class="mb-0" style="font-size: 0.75rem;">Menu ini digunakan untuk menambah usaha yang belum terdaftar</span>
                    </div>
                </div>
                <div class="modal-body pt-0 mt-2" style="height: auto;">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-control-label mb-0">Kecamatan <span class="text-danger">*</span></label>
                            <p id="subdistrict-add" style="font-size: 0.875rem;"></p>
                            <input type="hidden" id="subdistrict-add-hidden" name="subdistrict-add-hidden">
                        </div>
                        <div class="col-md-4">
                            <label class="form-control-label mb-0">Desa <span class="text-danger">*</span></label>
                            <p id="village-add" style="font-size: 0.875rem;"></p>
                            <input type="hidden" id="village-add-hidden" name="village-add-hidden">
                        </div>
                        <div id="sls_div" class="col-md-4">
                            <label class="form-control-label mb-0">SLS <span class="text-danger">*</span></label>
                            <p id="sls-add" style="font-size: 0.875rem;"></p>
                            <input type="hidden" id="sls-add-hidden" name="sls-add-hidden">
                        </div>
                        <div class="col-md-12">
                            <label class="form-control-label">Nama Usaha <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" placeholder="Nama Usaha" id="name-add" name="name-add">
                            <p id="name-add-error" style="display: none; font-size: 0.6rem; color:red"></p>
                        </div>
                    </div>

                    <div>
                        <p id="loading-add" style="visibility: hidden;" class="text-warning mt-3">Loading...</p>
                    </div>
                </div>

                <div class="modal-footer pt-0">
                    <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">Batal</button>
                    <button onclick="onAdd()" type="button" class="btn btn-primary mb-0">Tambah</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header pb-1">
                    <div>
                        <h5>Hapus Usaha Berikut?</h5>
                    </div>
                </div>
                <div class="modal-body pt-0 mt-2" style="height: auto;">
                    <div class="row">
                        <div class="col-md-4">
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
    function toggleAddButton() {
        isAddDisabled = true;
        if (document.getElementById('sls').value != 0) {
            isAddDisabled = false;
        }
        document.getElementById('add-button').disabled = isAddDisabled;
    }
</script>
<script>
    toggleAddButton()
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
    ].forEach(config => {
        $(config.selector).select2({
            placeholder: config.placeholder,
            allowClear: true,
        });
    });

    $('#subdistrict').on('change', function() {
        loadVillage(null, null);
    });
    $('#village').on('change', function() {
        loadSls(null, null);
    });
    $('#sls').on('change', function() {
        loadDirectory(null);
    });

    function loadVillage(subdistrictid = null, selectedvillage = null) {
        emptyDirectoryList()
        let id = $('#subdistrict').val();
        if (subdistrictid != null) {
            id = subdistrictid;
        }
        $('#village').empty();
        $('#village').append(`<option value="0" disabled selected>Processing...</option>`);
        $('#sls').empty();
        $('#sls').append(`<option value="0" disabled selected>Processing...</option>`);

        if (id != null) {
            $.ajax({
                type: 'GET',
                url: '/desa/' + id,
                success: function(response) {

                    $('#village').empty();
                    $('#village').append(`<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                    $('#sls').empty();
                    $('#sls').append(`<option value="0" disabled selected> -- Pilih SLS -- </option>`);
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
            $('#sls').empty();
            $('#sls').append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
        }
        toggleAddButton()
    }

    function loadSls(villageid = null, selectedsls = null) {
        emptyDirectoryList()
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
        toggleAddButton()
    }

    function loadDirectory(bsid = null) {
        emptyDirectoryList()
        let id = $('#sls').val();
        if (bsid != null) {
            id = bsid;
        }
        const resultDiv = document.getElementById('directorylist');
        resultDiv.innerHTML = '<p class="text-warning">Loading<p/>';

        if (id != null) {
            $.ajax({
                type: 'GET',
                url: '/sls-directory/' + id,
                success: function(response) {

                    selectedBS = id;

                    directories = []

                    const resultDiv = document.getElementById('directorylist');
                    resultDiv.innerHTML = '';

                    response.forEach(item => {
                        directories.push(item);

                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'col-md-4 col-sm-6 col-xs-12 p-1';
                        itemDiv.style = "cursor: pointer;"

                        let button = ''
                        if (!item.is_new) {
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
                            <button data-row='${JSON.stringify(item)}' onclick="onDeleteModal(this)" class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" type="button">
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

                    if (response.length == 0) {
                        resultDiv.innerHTML = `<p class="text-small text-warning">Tidak ada direktori di SLS ini</p>`
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    const resultDiv = document.getElementById('directorylist');
                    resultDiv.innerHTML = `
                        <div class="d-flex">
                            <span class="mr-2">Gagal Menampilkan Sampel</span>
                            <button onclick="loadSample(null)" class="btn btn-sm btn-outline-primary">Muat Ulang</button>
                        </div>
                `;
                }
            });
        } else {
            resultDiv.innerHTML = '';
        }
        toggleAddButton()
    }

    function openUpdateDirectoryModal(item) {
        $('#updateDirectoryModal').modal('show');

        document.getElementById('modaltitle').innerHTML = item.name
        document.getElementById('modalsubtitle').innerHTML = "[" + item.sls.id + "] " +
            item.subdistrict.name + ", " + item.village.name + ", " + item.sls.name
        document.getElementById('business_id').value = item.id

        document.getElementById('status_error').style.display = 'none'

        $('#status').val(item.status.id).trigger('change');
    }

    function openUpdateNewModal(item) {
        $('#updateNewModal').modal('show');

        document.getElementById('modaltitle-new').innerHTML = item.name
        document.getElementById('modalsubtitle-new').innerHTML = "[" + item.sls.id + "] " +
            item.subdistrict.name + ", " + item.village.name + ", " + item.sls.name
        document.getElementById('business_id_new').value = item.id

        document.getElementById('status_error_new').style.display = 'none'
        document.getElementById('name-new').value = item.name
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

    function openAddModal() {
        if (document.getElementById('sls').value != null) {
            document.getElementById('name-add-error').innerHTML = ''
            document.getElementById('loading-add').style.visibility = 'hidden'
            document.getElementById('name-add').value = ''

            $('#addModal').modal('show');
            const subdistrictAdd = document.getElementById('subdistrict');
            document.getElementById('subdistrict-add').innerHTML = "[" + subdistrictAdd.value + "] " +
                getAreaName(subdistrictAdd.options[subdistrictAdd.selectedIndex].text);
            document.getElementById('subdistrict-add-hidden').value = subdistrictAdd.value;

            const villageAdd = document.getElementById('village');
            document.getElementById('village-add').innerHTML = "[" + villageAdd.value + "] " +
                getAreaName(villageAdd.options[villageAdd.selectedIndex].text);
            document.getElementById('village-add-hidden').value = villageAdd.value;

            const slsAdd = document.getElementById('sls');
            document.getElementById('sls-add').innerHTML = "[" + slsAdd.value + "] " +
                getAreaName(slsAdd.options[slsAdd.selectedIndex].text);
            document.getElementById('sls-add-hidden').value = slsAdd.value;

        }
    }

    function getAreaName(input) {
        const match = input.match(/\] (.+)/);
        return match ? match[1] : "";
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
                url: `/directory/edit/sls/${id}`,
                type: 'PATCH',
                data: updateData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    loadDirectory(null)
                    $('#updateDirectoryModal').modal('hide');
                    document.getElementById('loading-save').style.visibility = 'hidden'
                },
                error: function(xhr, status, error) {
                    document.getElementById('loading-save').style.visibility = 'hidden'
                }
            });
        }
    }

    function validateAddNewForm(input, error) {
        var name_valid = true
        if (document.getElementById(input).value == "") {
            name_valid = false
            document.getElementById(error).style.display = 'block'
            document.getElementById(error).innerHTML = 'Nama Usaha harus diisi'
        } else {
            document.getElementById(error).style.display = 'none'
        }

        return name_valid
    }

    function onAdd() {
        if (validateAddNewForm('name-add', 'name-add-error')) {
            document.getElementById('loading-add').style.visibility = 'visible'

            $.ajax({
                url: '/sls-directory',
                type: 'POST',
                data: {
                    name: document.getElementById('name-add').value,
                    subdistrict: document.getElementById('subdistrict-add-hidden').value,
                    village: document.getElementById('village-add-hidden').value,
                    sls: document.getElementById('sls-add-hidden').value,
                },
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    loadDirectory(null)
                    $('#addModal').modal('hide');
                    document.getElementById('loading-add').style.visibility = 'hidden'
                },
                error: function(xhr, status, error) {
                    document.getElementById('loading-add').innerHTML = 'Gagal menambahkan usaha'
                }
            });
        }
    }

    function onDelete() {
        document.getElementById('loading-delete').style.visibility = 'visible'

        $.ajax({
            url: `/sls-directory/${document.getElementById('id-hidden').value}`,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                loadDirectory(null)
                $('#deleteModal').modal('hide');
                document.getElementById('loading-delete').style.visibility = 'hidden'
            },
            error: function(xhr, status, error) {
                document.getElementById('loading-delete').innerHTML = 'Gagal menghapus usaha'
            }
        });
    }

    function onSaveNew() {
        document.getElementById('status_error_new').style.visibility = 'hidden'

        if (validateAddNewForm('name-new', 'name-new-error')) {
            document.getElementById('loading-save-new').style.visibility = 'visible'

            id = document.getElementById('business_id_new').value
            var updateData = {
                name: document.getElementById('name-new').value,
                new: true,
            };

            $.ajax({
                url: `/directory/edit/sls/${id}`,
                type: 'PATCH',
                data: updateData,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    loadDirectory(null)
                    $('#updateNewModal').modal('hide');
                    document.getElementById('loading-save-new').style.visibility = 'hidden'
                },
                error: function(xhr, status, error) {
                    document.getElementById('loading-save-new').style.visibility = 'hidden'
                }
            });
        }
    }

    function emptyDirectoryList() {
        const resultDiv = document.getElementById('directorylist');
        resultDiv.innerHTML = '';
    }
</script>
@endpush

@endsection