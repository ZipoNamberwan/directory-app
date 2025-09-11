@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Petugas'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">

        @if (session('success-edit') || session('success-create'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="alert-icon"><i class="ni ni-like-2"></i></span>
                <span class="alert-text"><strong>Success!</strong> {{ session('success-create') }}
                    {{ session('success-edit') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('success-delete') || session('error-delete'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <span class="alert-icon"><i class="ni ni-dislike-2"></i></span>
                <span class="alert-text">{{ session('success-delete') }} {{ session('error-delete') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <div>
                        <h4 class="text-capitalize mb-3 d-flex align-items-center">Daftar Petugas</h4>
                        <div class="text-sm mb-3">
                            <p class="mb-2">
                                Menu ini digunakan untuk mengelola akun petugas untuk Login <strong>Kendedes Web</strong>, 
                                <strong>Kendedes Mobile</strong>, maupun <strong>Ken Arok</strong>.
                            </p>
                            <ul class="mb-2 ps-4">
                                <li>
                                    Karena Ken Dedes dan Ken Arok menggunakan <strong>database yang sama</strong>, 
                                    maka satu akun petugas bisa ditambahkan:
                                    <ul>
                                        <li><strong>Hanya sebagai akun Ken Arok</strong>, atau</li>
                                        <li><strong>Hanya sebagai akun Ken Dedes Mobile</strong>, atau</li>
                                        <li><strong>Keduanya sekaligus</strong></li>
                                    </ul>
                                </li>
                                <li>
                                    Akun mana saja yang merupakan akun Ken Arok dan Kendedes bisa difilter menggunakan 
                                    <strong>filter jenis akun</strong>.
                                </li>
                                <li>
                                    Untuk mengubah jenis akun petugas, silakan edit petugas tersebut dan centang atau hilangkan centang pada opsi
                                    <strong>Ken Arok</strong> atau <strong>Ken Dedes Mobile</strong>.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-1">
                <div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <a href="/users/create" class="btn btn-primary btn-lg ms-auto p-2 m-0" role="button">
                                <span class="btn-inner--icon"><i class="fas fa-plus"></i></span>
                                <span class="ml-3 btn-inner--text">Tambah</span>
                            </a>
                        </div>
                    </div>
                    <div class="row mb-3">
                        @hasrole('adminprov')
                            <div class="col-md-3">
                                <label class="form-control-label">Satker <span class="text-danger">*</span></label>
                                <select id="organization" class="form-control" data-toggle="select">
                                    <option value="0" disabled selected> -- Pilih Satker -- </option>
                                    @foreach ($organizations as $organization)
                                        <option value="{{ $organization->id }}">
                                            [{{ $organization->short_code }}] {{ $organization->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endhasrole
                        <div class="col-md-3">
                            <label class="form-control-label">Role <span class="text-danger">*</span></label>
                            <select id="role" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Role -- </option>
                                <option value="pcl">PCL</option>
                                <option value="pml">PML</option>
                                <option value="operator">Operator</option>
                                <option value="adminkab">Admin Kabupaten</option>
                                <option value="adminprov">Admin Provinsi</option>
                                <option value="viewer">Viewer</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-control-label">Filter Jenis Akun <span class="text-danger">*</span></label>
                            <select id="type" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Jenis Akun -- </option>
                                <option value="all">Semua</option>
                                <option value="kendedes">Akun Ken Dedes Mobile</option>
                                <option value="kenarok">Akun Ken Arok</option>
                            </select>
                        </div>
                        @hasrole('adminprov')
                        <div class="col-md-3">
                            <label class="form-control-label">Tampikan Yang Diijinkan Upload SW Maps<span class="text-danger">*</span></label>
                            <div class="form-check form-switch">
                                <input onchange="toggleSwmaps()" value="1" class="form-check-input" 
                                    type="checkbox" id="is_allowed_swmaps">
                                <label id="switchlabel" class="form-check-label" for="is_allowed_swmaps">Tidak</label>
                            </div>
                        </div>
                        @endhasrole
                    </div>
                    <table id="myTable" class="align-items-center mb-0 text-sm">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama dan email</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Role</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Jenis Akun</th>
                                @hasrole('adminprov')
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Bisa Upload SW Maps?</th>
                                @endhasrole
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Permission</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Satker</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
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
        <script src="/vendor/sweetalert2/sweetalert2.js"></script>

        <script>
            const selectConfigs = [{
                selector: '#role',
                placeholder: 'Pilih Peran'
            }, {
                selector: '#organization',
                placeholder: 'Pilih Satker'
            }, {
                selector: '#type',
                placeholder: 'Pilih Jenis Akun'
            }];

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
                '#role': () => {
                    renderTable()
                },
                '#organization': () => {
                    renderTable()
                },
                '#type': () => {
                    renderTable()
                },
            };

            Object.entries(eventHandlers).forEach(([selector, handler]) => {
                $(selector).on('change', handler);
            });

             function getFilterUrl(filter) {
                var filterUrl = ''
                if (filter === 'is_allowed_swmaps') {
                    const checkbox = document.getElementById('is_allowed_swmaps');

                    if (checkbox.checked) {
                        filterUrl = `&${filter}=1`;
                    } else {
                        filterUrl = `&${filter}=0`;
                    }
                } else {
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
                }

                return filterUrl
            }

            function renderTable() {
                filterUrl = ''
                filterTypes = ['role', 'organization', 'is_allowed_swmaps', 'type']
                filterTypes.forEach(f => {
                    filterUrl += getFilterUrl(f)
                });

                table.ajax.url('/users/data?' + filterUrl).load();
            }
        </script>
        <script>
            let table = new DataTable('#myTable', {
                order: [],
                serverSide: true,
                processing: true,
                ajax: {
                    url: '/users/data',
                    type: 'GET',
                },
                responsive: true,
                columns: [{
                        responsivePriority: 1,
                        width: "10%",
                        data: "firstname",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `
                                    <div class="d-flex flex-column justify-content-center my-2">
                                        <h6 class="mb-0 text-sm">${data}</h6>
                                        <p class="text-xs text-secondary mb-0">${row.email}</p>
                                    </div>
                                `
                            }
                            return data
                        }
                    },
                    {
                        responsivePriority: 2,
                        width: "5%",
                        data: "roles",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                const roleNames = data.map(role => role.name).join(', ');
                                return `<div class="my-1"> 
                                            <p style='font-size: 0.7rem' class='text-secondary mb-0'>${roleNames}</p>
                                        </div>`
                            }
                            return data[0].name
                        }
                    },
                    {
                        responsivePriority: 2,
                        width: "10%",
                        data: "is_kendedes_user",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                let type = ''
                                if (data === 1){
                                    type = type + `<span class="badge bg-success me-2">KDM</span>`
                                }
                                if (row.is_kenarok_user === 1) {
                                    type = type + `<span class="badge bg-info">Ken Arok</span>`
                                }
                                return '<div class="d-flex">'+type+'</div>'
                            }
                            return data
                        }
                    },
                    @hasrole('adminprov')
                    {
                        responsivePriority: 2,
                        width: "10%",
                        data: "is_allowed_swmaps",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                let label = ''
                                if (data == 1){
                                    label = `<span class="badge bg-success me-2">Ya</span>`
                                } else {
                                    label = `<span class="badge bg-danger">Tidak</span>`
                                }
                                return label
                            }
                            return data
                        }
                    },
                    @endhasrole
                    {
                        responsivePriority: 2,
                        width: "10%",
                        data: "permission",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                let permissions = [];
                                
                                if (data && data.includes('edit_business')) {
                                    permissions.push('<span class="badge bg-info me-1 mb-1">Bisa Edit</span>');
                                }
                                
                                if (data && data.includes('delete_business')) {
                                    permissions.push('<span class="badge bg-warning mb-1">Bisa Hapus</span>');
                                }
                                
                                if (permissions.length === 0) {
                                    return '<span class="text-muted">-</span>';
                                }
                                
                                return '<div class="d-flex flex-wrap m-1">' + permissions.join('') + '</div>';
                            }
                            return data;
                        }
                    },
                    {
                        responsivePriority: 2,
                        width: "10%",
                        data: "organization",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (data != null) {
                                    return `<div class="my-1"> 
                                                <p style='font-size: 0.7rem' class='text-secondary mb-0'>${data.long_code}</p>
                                                <p style='font-size: 0.7rem' class='text-secondary mb-0'>${data.name}</p>
                                            </div>`
                                }
                            }
                            return ''
                        }
                    },
                    {
                        responsivePriority: 3,
                        width: "5%",
                        data: "id",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `
                        <div class="d-flex align-items-center justify-content-start my-2 gap-2">
                            @hasrole('adminprov')
                            <a href="${`{{route('impersonate', ':id' )}}`.replace(':id', row.id)}" class="px-2 py-1 m-0 btn btn-icon btn-outline-success btn-sm" role="button">
                                <span class="btn-inner--icon"><i class="fas fa-hat-cowboy"></i></span>
                            </a>
                            @endhasrole
                            
                            <a href="/users/${data}/edit" class="px-2 py-1 m-0 btn btn-icon btn-outline-info btn-sm" role="button">
                                <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
                            </a>
                            <form class="d-inline" id="formdelete${data}" name="formdelete${data}" onSubmit="deleteUser('${data}','${row.firstname}')" 
                                method="POST" action="/users/${data}">
                                @method('delete')
                                @csrf
                                <button class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" type="submit">
                                    <span class="btn-inner--icon"><i class="fas fa-trash"></i></span>
                                </button>
                            </form>
                        </div>
                        `
                            }
                            return data[0].name
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

            function deleteUser(id, name) {
                event.preventDefault();
                Swal.fire({
                    title: `Yakin Hapus Petugas Ini? ${name}`,
                    text: 'Menghapus petugas akan menghapus seluruh usaha yang sudah ditaging. Yakin?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya',
                    cancelButtonText: 'Tidak',
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('formdelete' + id).submit();
                    }
                })
            }

            function toggleSwmaps() {
                const checkbox = document.getElementById('is_allowed_swmaps');
                const label = document.getElementById('switchlabel');

                if (checkbox.checked) {
                    label.textContent = 'Ya';
                } else {
                    label.textContent = 'Tidak';
                }

                renderTable();
            }
        </script>
    @endpush
@endsection
