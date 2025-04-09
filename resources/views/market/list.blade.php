@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Daftar Assignment'])
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

        <div class="card z-index-2 h-100">
            <div class="card-header pb-0 pt-3 bg-transparent">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-capitalize">Daftar Assignment</h6>
                        <p class="text-sm mb-0">
                            <span>Daftar assignment yang sudah dilakukan</span>
                        </p>
                    </div>
                    <a href="/pasar-assignment/create" class="btn btn-primary mb-0 p-2">Tambah</a>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-control-label">Pasar <span class="text-danger">*</span></label>
                        <select id="market" name="market" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Pasar -- </option>
                            @foreach ($markets as $market)
                                <option value="{{ $market->id }}" {{ old('market') == $market->id ? 'selected' : '' }}>
                                    {{ $market->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">User <span class="text-danger">*</span></label>
                        <select id="user" name="user" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih User -- </option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}" {{ old('user') == $user->id ? 'selected' : '' }}>
                                    {{ $user->firstname }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <table id="myTable" class="align-items-center mb-0 text-sm">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Pasar</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama User</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

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
                selector: '#market',
                placeholder: 'Pilih Pasar'
            },
            {
                selector: '#user',
                placeholder: 'Pilih User'
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
            '#market': () => {
                renderTable()
            },
            '#user': () => {
                renderTable()
            },
        };
        Object.entries(eventHandlers).forEach(([selector, handler]) => {
            $(selector).on('change', handler);
        });
    </script>

    <script>
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
            filterTypes = ['market', 'user']
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            table.ajax.url('/pasar-assignment/pivot?' + filterUrl).load();
        }

        let table = new DataTable('#myTable', {
            order: [],
            serverSide: true,
            processing: true,
            // deferLoading: 0,
            ajax: {
                url: '/pasar-assignment/pivot',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "market_name",
                    type: "text",
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "user_firstname",
                    type: "text",
                },
                {
                    responsivePriority: 3,
                    width: "10%",
                    data: "id",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return `
                                <form id="formdelete${data}" name="formdelete${data}" onSubmit="deleteAssignment('${data}')" 
                                    class="my-2" action="/pasar-assignment/${data}" method="POST">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-outline-danger btn-sm ms-auto p-1 m-0" type="submit">
                                        <i class="fas fa-trash mx-1"></i>
                                    </button>
                                </form>
                        `;
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

        function deleteAssignment(id) {
            event.preventDefault();
            Swal.fire({
                title: `Hapus Assignment Ini?`,
                text: 'Menghapus assignment tidak mempengaruhi data pasar yang telah diupload',
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
