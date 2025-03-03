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
    @include('layouts.navbars.auth.topnav', ['title' => 'Sampai Level SLS'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card z-index-2 h-100">
            <div class="card-header pb-0 pt-3 bg-transparent">
                <div class="d-flex alignt-items-center">
                    <h6 class="text-capitalize">Daftar Direktori Sampai Level SLS</h6>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row mb-2">
                    <div class="col-sm-12 col-md-3 my-1">
                        <label class="form-control-label">Status Pemutakhiran <span class="text-danger">*</span></label>
                        <select id="status" name="status" class="form-control" data-toggle="select" required>
                            <option value="0" disabled selected> -- Filter Status -- </option>
                            <option value="all"> Semua </option>
                            @foreach ($statuses as $status)
                                <option value="{{ $status->id }}">{{ $status->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @hasrole('adminprov')
                        <div class="col-sm-12 col-md-3 my-1">
                            <label class="form-control-label">Kabupaten <span class="text-danger">*</span></label>
                            <select id="regency" name="regency" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Filter Kabupaten -- </option>
                                @foreach ($regencies as $regency)
                                    <option value="{{ $regency->id }}">
                                        [{{ $regency->short_code }}] {{ $regency->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endhasrole
                    <div class="col-sm-12 col-md-3 my-1">
                        <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                        <select id="subdistrict" name="subdistrict" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Filter Kecamatan -- </option>
                            @foreach ($subdistricts as $subdistrict)
                                <option value="{{ $subdistrict->id }}">
                                    [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-12 col-md-3 my-1">
                        <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                        <select id="village" name="village" class="form-control" data-toggle="select"
                            name="village"></select>
                    </div>
                    <div id="sls_div" class="col-sm-12 col-md-3 my-1">
                        <label class="form-control-label">SLS <span class="text-danger">*</span></label>
                        <select id="sls" name="sls" class="form-control" data-toggle="select"></select>
                    </div>
                    <div class="col-sm-12 col-md-3 my-1">
                        <label class="form-control-label">Status Assignment <span class="text-danger">*</span></label>
                        <select id="assignment" name="assignment" class="form-control" data-toggle="select" required>
                            <option value="0" disabled selected> -- Filter Assignment -- </option>
                            <option value="all"> Semua </option>
                            <option value="1">Sudah Diassign</option>
                            <option value="0">Belum Diassign</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3">
                    <button id="add-button" onclick="openAddModal()"
                        class="btn btn-primary btn-sm ms-auto p-2 m-0">Tambah</button>
                    <table id="myTable" class="align-items-center mb-0 text-sm">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Usaha</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Wilayah</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Status</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">PCL</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="modal fade" id="updateDirectoryModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
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
                        <select id="statusUpdate" name="statusUpdate" class="form-control" data-toggle="select"
                            required>
                            <option value="0" disabled selected> -- Pilih Status -- </option>
                            @foreach ($statuses as $status)
                                @if ($status->name != 'Baru')
                                    <option value="{{ $status->id }}">{{ $status->name }}</option>
                                @endif
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

        <div class="modal fade" id="updateNewModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
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
                        <input type="text" class="form-control" placeholder="Nama Usaha" id="name-new"
                            name="name-new">
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

        <div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header pb-1">
                        <div>
                            <h5>Tambah Usaha Baru</h5>
                            <span class="mb-0" style="font-size: 0.75rem;">Menu ini digunakan untuk menambah usaha yang
                                belum terdaftar</span>
                        </div>
                    </div>
                    <div class="modal-body pt-0 mt-2" style="height: auto;">
                        <div class="row">
                            @hasrole('adminprov')
                                <div class="col-md-12">
                                    <label class="form-control-label">Kabupaten <span class="text-danger">*</span></label>
                                    <select id="regencyAdd" name="regencyAdd" class="form-control" data-toggle="select">
                                        <option value="0" disabled selected> -- Pilih Kabupaten -- </option>
                                        @foreach ($regencies as $regency)
                                            <option value="{{ $regency->id }}">
                                                [{{ $regency->short_code }}] {{ $regency->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endhasrole
                            <div class="col-md-12">
                                <label class="form-control-label">Kecamatan <span class="text-danger">*</span></label>
                                <select id="subdistrictAdd" name="subdistrictAdd" class="form-control"
                                    data-toggle="select">
                                    <option value="0" disabled selected> -- Pilih Kecamatan -- </option>
                                    @foreach ($subdistricts as $subdistrict)
                                        <option value="{{ $subdistrict->id }}">
                                            [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-control-label">Desa <span class="text-danger">*</span></label>
                                <select id="villageAdd" name="villageAdd" class="form-control" data-toggle="select"
                                    name="village"></select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-control-label">SLS <span class="text-danger">*</span></label>
                                <select id="slsAdd" name="slsAdd" class="form-control"
                                    data-toggle="select"></select>
                                <p id="slsAddError" style="display: none; font-size: 0.6rem; color:red"></p>
                            </div>
                            <div class="col-md-12">
                                <label class="form-control-label">Nama Usaha <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" placeholder="Nama Usaha" id="nameAdd"
                                    name="nameAdd">
                                <p id="nameAddError" style="display: none; font-size: 0.6rem; color:red"></p>
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

        <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
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
        <script src="/vendor/datatables/dataTables.min.js"></script>
        <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>
        <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
        <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

        <script>
            [{
                    selector: '#regency',
                    placeholder: 'Filter Kabupaten'
                },
                {
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
                    selector: '#regencyAdd',
                    placeholder: 'Pilih Kecamatan'
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
                {
                    selector: '#status',
                    placeholder: 'Filter Status'
                },
                {
                    selector: '#statusUpdate',
                    placeholder: 'Pilih Status'
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

            $('#regency').on('change', function() {
                loadSubdistrict('', null, null);
                renderTable();
            });
            $('#subdistrict').on('change', function() {
                loadVillage('', null, null);
                renderTable();
            });
            $('#village').on('change', function() {
                loadSls('', null, null);
                renderTable();
            });
            $('#sls').on('change', function() {
                renderTable();
            });
            $('#regencyAdd').on('change', function() {
                loadSubdistrict('Add', null, null);
            });
            $('#subdistrictAdd').on('change', function() {
                loadVillage('Add', null, null);
            });
            $('#villageAdd').on('change', function() {
                loadSls('Add', null, null);
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
                if (e != null) {
                    var filterselected = e.options[e.selectedIndex];
                    if (filterselected != null) {
                        var filterid = filterselected.value
                        if (filterid != 0) {
                            filterUrl = `&${filter}=` + filterid
                        }
                    }
                }

                return filterUrl
            }

            function renderTable() {
                filterUrl = ''
                filterTypes = ['status', 'regency', 'subdistrict', 'village', 'sls', 'assignment']
                filterTypes.forEach(f => {
                    filterUrl += getFilterUrl(f)
                });

                table.ajax.url('/sls-directory/data?' + filterUrl).load();
            }

            function loadSubdistrict(group, regencyid = null, selectedsubdistrict = null) {
                let regencySelector = `#regency${group}`;
                let subdistrictSelector = `#subdistrict${group}`;
                let villageSelector = `#village${group}`;
                let slsSelector = `#sls${group}`;

                let id = $(regencySelector).val();
                if (regencyid != null) {
                    id = regencyid;
                }

                $(subdistrictSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

                if (id != null) {
                    $.ajax({
                        type: 'GET',
                        url: '/kec/' + id,
                        success: function(response) {
                            $(subdistrictSelector).empty().append(
                                `<option value="0" disabled selected> -- Filter Kecamatan -- </option>`);
                            $(villageSelector).empty().append(
                                `<option value="0" disabled selected> -- Filter Desa -- </option>`);
                            $(slsSelector).empty().append(
                                `<option value="0" disabled selected> -- Filter SLS -- </option>`);

                            response.forEach(element => {
                                $(subdistrictSelector).append(
                                    `<option value="${element.id}" ${selectedsubdistrict == element.id ? 'selected' : ''}>[${element.short_code}] ${element.name}</option>`
                                );
                            });
                        }
                    });
                } else {
                    $(subdistrictSelector).empty().append(`<option value="0" disabled> -- Filter Kecamatan -- </option>`);
                    $(villageSelector).empty().append(`<option value="0" disabled> -- Filter Desa -- </option>`);
                    $(slsSelector).empty().append(`<option value="0" disabled> -- Filter SLS -- </option>`);
                }
            }

            function loadVillage(group, subdistrictid = null, selectedvillage = null) {
                let subdistrictSelector = `#subdistrict${group}`;
                let villageSelector = `#village${group}`;
                let slsSelector = `#sls${group}`;

                let id = $(subdistrictSelector).val();
                if (subdistrictid != null) {
                    id = subdistrictid;
                }

                $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

                if (id != null) {
                    $.ajax({
                        type: 'GET',
                        url: '/desa/' + id,
                        success: function(response) {
                            $(villageSelector).empty().append(
                                `<option value="0" disabled selected> -- Filter Desa -- </option>`);
                            $(slsSelector).empty().append(
                                `<option value="0" disabled selected> -- Filter SLS -- </option>`);

                            response.forEach(element => {
                                $(villageSelector).append(
                                    `<option value="${element.id}" ${selectedvillage == element.id ? 'selected' : ''}>[${element.short_code}] ${element.name}</option>`
                                );
                            });
                        }
                    });
                } else {
                    $(villageSelector).empty().append(`<option value="0" disabled> -- Filter Desa -- </option>`);
                    $(slsSelector).empty().append(`<option value="0" disabled> -- Filter SLS -- </option>`);
                }
            }

            function loadSls(group, villageid = null, selectedsls = null) {
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
                                $(slsSelector).append(
                                    `<option value="${element.id}" ${selectedsls == element.id ? 'selected' : ''}>[${element.short_code}] ${element.name}</option>`
                                );
                            });
                        }
                    });
                } else {
                    $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
                }
            }

            function openUpdateDirectoryModal(itemString) {
                const item = JSON.parse(itemString.getAttribute('data-row'));
                $('#updateDirectoryModal').modal('show');

                document.getElementById('modaltitle').innerHTML = item.name + (item.owner ? ' (' + item.owner + ')' : '')
                
                const areaDetail = "[" + item.sls.id + "] " +
                    item.subdistrict.name + ", " + item.village.name + ", " + item.sls.name
                const detailsArray = [
                    `${areaDetail}`,
                    // item.initial_address && `Alamat awal: ${item.initial_address}`,
                    item.kbli && `KBLI: ${item.kbli}`,
                    item.category && `Kategori: ${item.category}`
                ].filter(Boolean);

                document.getElementById('modalsubtitle').innerHTML = detailsArray.join('<br>');

                document.getElementById('business_id').value = item.id

                document.getElementById('status_error').style.display = 'none'

                $('#statusUpdate').val(item.status.id).trigger('change');
            }

            function openUpdateNewModal(itemString) {
                const item = JSON.parse(itemString.getAttribute('data-row'));
                $('#updateNewModal').modal('show');

                document.getElementById('modaltitle-new').innerHTML = item.name + (item.owner ? ' (' + item.owner + ')' : '')
                
                const areaDetail = "[" + item.sls.id + "] " +
                    item.subdistrict.name + ", " + item.village.name + ", " + item.sls.name
                const detailsArray = [
                    `${areaDetail}`,
                    // item.initial_address && `Alamat awal: ${item.initial_address}`,
                    item.kbli && `KBLI: ${item.kbli}`,
                    item.category && `Kategori: ${item.category}`
                ].filter(Boolean);

                document.getElementById('modalsubtitle-new').innerHTML = detailsArray.join('<br>');

                document.getElementById('business_id_new').value = item.id

                document.getElementById('status_error_new').style.display = 'none'
                document.getElementById('name-new').value = item.name
            }

            function openAddModal() {
                $('#addModal').modal('show');

                document.getElementById('nameAddError').innerHTML = ''
                document.getElementById('loading-add').style.visibility = 'hidden'
                document.getElementById('nameAdd').value = ''

                $('#subdistrictAdd').val(null).trigger('change');
                $('#villageAdd').val(null).trigger('change');
                $('#slsAdd').val(null).trigger('change');

            }

            function openDeleteModal(itemString) {
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

            function validate() {
                var status_valid = true
                if (document.getElementById('statusUpdate').value == 0 || document.getElementById('statusUpdate').value ==
                    null) {
                    status_valid = false
                    document.getElementById('status_error').style.display = 'block'
                } else {
                    document.getElementById('status_error').style.display = 'none'
                }

                return status_valid
            }

            function validateSaveNew(input, error) {
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

            function validateAdd() {
                var name_valid = true
                if (document.getElementById('nameAdd').value == "") {
                    name_valid = false
                    document.getElementById('nameAddError').style.display = 'block'
                    document.getElementById('nameAddError').innerHTML = 'Nama Usaha harus diisi'
                } else {
                    document.getElementById('nameAddError').style.display = 'none'
                }

                var sls_valid = true
                if (document.getElementById('slsAdd').value == "" || document.getElementById('slsAdd').value == "0") {
                    sls_valid = false
                    document.getElementById('slsAddError').style.display = 'block'
                    document.getElementById('slsAddError').innerHTML = 'SLS harus diisi'
                } else {
                    document.getElementById('slsAddError').style.display = 'none'
                }

                return name_valid && sls_valid
            }

            function onSave() {
                document.getElementById('status_error').style.visibility = 'hidden'

                if (validate()) {
                    document.getElementById('loading-save').style.visibility = 'visible'

                    id = document.getElementById('business_id').value
                    var updateData = {
                        status: document.getElementById('statusUpdate').value,
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
                            // renderTable()
                            table.ajax.reload(null, false)
                            $('#updateDirectoryModal').modal('hide');
                            document.getElementById('loading-save').style.visibility = 'hidden'
                        },
                        error: function(xhr, status, error) {
                            document.getElementById('loading-save').style.visibility = 'hidden'
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
                        // renderTable()
                        table.ajax.reload(null, false)
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

                if (validateSaveNew('name-new', 'name-new-error')) {
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
                            // renderTable()
                            table.ajax.reload(null, false)
                            $('#updateNewModal').modal('hide');
                            document.getElementById('loading-save-new').style.visibility = 'hidden'
                        },
                        error: function(xhr, status, error) {
                            document.getElementById('loading-save-new').style.visibility = 'hidden'
                        }
                    });
                }
            }

            function onAdd() {
                if (validateAdd()) {
                    document.getElementById('loading-add').style.visibility = 'visible'

                    $.ajax({
                        url: '/sls-directory',
                        type: 'POST',
                        data: {
                            name: document.getElementById('nameAdd').value,
                            regency: document.getElementById('regencyAdd')?.value,
                            subdistrict: document.getElementById('subdistrictAdd').value,
                            village: document.getElementById('villageAdd').value,
                            sls: document.getElementById('slsAdd').value,
                        },
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            // renderTable()
                            table.ajax.reload(null, false)
                            $('#addModal').modal('hide');
                            document.getElementById('loading-add').style.visibility = 'hidden'
                        },
                        error: function(xhr, status, error) {
                            document.getElementById('loading-add').innerHTML = 'Gagal menambahkan usaha'
                        }
                    });
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
                            return data
                        }
                    },
                    {
                        responsivePriority: 3,
                        width: "10%",
                        data: "status",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return '<p class="mb-0"><span class="badge bg-gradient-' + data.color + '">' +
                                    data.name + '</span></p>';
                            }
                            return data;
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
                            return data;
                        }
                    },
                    {
                        responsivePriority: 4,
                        width: "10%",
                        data: "id",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (!row.is_new) {
                                    return `
                                <button data-row='${escapeJsonForHtml(JSON.stringify(row))}' onclick="openUpdateDirectoryModal(this)" class="px-2 py-1 m-0 btn btn-icon btn-outline-primary btn-sm" type="button">
                                    <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
                                </button>`;
                                } else {
                                    return `
                            <button data-row='${escapeJsonForHtml(JSON.stringify(row))}' onclick="openUpdateNewModal(this)" class="px-2 py-1 m-0 btn btn-icon btn-outline-primary btn-sm" type="button">
                                <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
                            </button>
                            <button data-row='${escapeJsonForHtml(JSON.stringify(row))}' onclick="openDeleteModal(this)" class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" type="button">
                                <span class="btn-inner--icon"><i class="fas fa-trash-alt"></i></span>
                            </button>
                            `;
                                }
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

            function escapeJsonForHtml(jsonString) {
                return jsonString
                    .replace(/"/g, '&quot;') // Escape double quotes
                    .replace(/'/g, '&#39;'); // Escape single quotes
            }
        </script>
    @endpush
@endsection
