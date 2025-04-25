@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Pasar'])
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
                    <h4 class="text-capitalize">Daftar Pasar</h4>
                    @hasrole('adminprov')
                        <a href="/pasar/manajemen/create" class="btn btn-primary btn-lg ms-auto p-2 m-0" role="button">
                            <span class="btn-inner--icon"><i class="fas fa-plus"></i></span>
                            <span class="ml-3 btn-inner--text">Tambah</span>
                        </a>
                    @endhasrole
                </div>
            </div>
            <div class="card-body pt-1">
                <div>
                    <div class="row mb-3">
                        @hasrole('adminprov')
                            <div class="col-md-3">
                                <label class="form-control-label">Kabupaten <span class="text-danger">*</span></label>
                                <select id="regency" class="form-control" data-toggle="select">
                                    <option value="0" disabled selected> -- Pilih Kabupaten -- </option>
                                    @foreach ($regencies as $regency)
                                        <option value="{{ $regency->id }}">
                                            [{{ $regency->short_code }}] {{ $regency->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endhasrole
                    </div>
                    <table id="myTable" class="align-items-center mb-0 text-sm">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Wilayah</th>
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
                selector: '#regency',
                placeholder: 'Pilih Kabupaten'
            }, ];

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
                '#regency': () => {
                    renderTable()
                },
            };

            Object.entries(eventHandlers).forEach(([selector, handler]) => {
                $(selector).on('change', handler);
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
                filterTypes = ['role', 'regency']
                filterTypes.forEach(f => {
                    filterUrl += getFilterUrl(f)
                });

                table.ajax.url('/pasar/manajemen/data?' + filterUrl).load();
            }
        </script>
        <script>
            var isAdmin = @json($isAdmin);

            let table = new DataTable('#myTable', {
                order: [],
                serverSide: true,
                processing: true,
                ajax: {
                    url: '/pasar/manajemen/data',
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
                        data: "village",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                return `
                                    <div class="d-flex flex-column justify-content-center my-2">
                                        <h6 class="mb-0 text-sm">[${data.id}]</h6>
                                        <p class="text-sm text-secondary mb-0">${row.subdistrict.name}, ${data.name}</p>
                                    </div>
                                `
                            }
                            return data
                        }
                    },
                    {
                        responsivePriority: 3,
                        width: "10%",
                        data: "id",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {

                                var adminButton = isAdmin ? `
                                        <a href="/pasar/manajemen/${data}/edit" class="px-2 py-1 m-0 btn btn-icon btn-outline-info btn-sm" role="button">
                                            <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
                                        </a>
                                        <form class="d-inline" id="formdelete${data}" name="formdelete${data}" onSubmit="deleteMarket('${data}','${row.name}')" 
                                            method="POST" action="/pasar/manajemen/${data}">
                                            @method('delete')
                                            @csrf
                                            <button class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" type="submit">
                                                <span class="btn-inner--icon"><i class="fas fa-trash"></i></span>
                                            </button>
                                        </form>
                                ` : ''

                                return `
                                    <div class="d-flex align-items-center justify-content-start my-2 gap-2">
                                        <form class="d-inline" name="formdownload${data}" onSubmit="downloadUser('${data}','${row.name}')" 
                                            method="POST" action="/pasar/manajemen/download/${data}">
                                            @method('post')
                                            @csrf
                                            <button class="px-2 py-1 m-0 btn btn-icon btn-outline-success btn-sm" type="submit">
                                                <span class="btn-inner--icon">
                                                    <i class="fas fa-download"></i>
                                                    Download Project
                                                </span>
                                            </button>
                                        </form>
                                        ${adminButton}
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

            function deleteMarket(id, name) {
                event.preventDefault();
                Swal.fire({
                    title: `Yakin Hapus Pasar Ini? ${name}`,
                    text: 'Menghapus pasar akan menghapus seluruh muatan pada pasar tersebut',
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
        </script>
    @endpush
@endsection
