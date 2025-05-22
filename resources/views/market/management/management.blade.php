@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .custom-note {
            background-color: #e0f0ff;
            /* light blue background */
            border-left: 6px solid #007bff;
            /* bold blue border */
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 6px;
            font-family: Arial, sans-serif;
            color: #004085;
            box-shadow: 0 2px 4px rgba(0, 123, 255, 0.2);
        }

        .custom-note strong {
            margin-bottom: 5px;
        }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Manajemen Sentra Ekonomi'])
    <div class="container-fluid py-4">

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
                <div class="d-flex justify-content-between align-items-center">
                    <h4 class="text-capitalize">Daftar Sentra Ekonomi</h4>
                    <div class="d-flex">
                        @hasrole('adminprov|adminkab')
                            <a href="/pasar/manajemen/create" class="me-2 btn btn-primary btn-lg ms-auto p-2 m-0"
                                role="button">
                                <span class="btn-inner--icon"><i class="fas fa-plus"></i></span>
                                <span class="ml-3 btn-inner--text">Tambah</span>
                            </a>
                        @endhasrole
                        <form action="/pasar/manajemen/download" class="me-2" method="POST">
                            @csrf
                            <input type="hidden" name="organization" id="organization_download">
                            <input type="hidden" name="market" id="market_download">
                            <button type="submit" class="btn btn-primary mb-0 p-2">Download</button>
                        </form>
                        <button onclick="refresh()" class="btn btn-outline-primary mb-0 p-2" data-bs-toggle="modal"
                            data-bs-target="#statusDialog">
                            <i class="fas fa-circle-info me-2"></i>
                            Status
                        </button>
                    </div>
                </div>
                <div class="custom-note">
                    <strong>Note:</strong><br>
                    Sentra Ekonomi yang tidak mempunyai usaha tidak bisa ditandai <code>selesai</code>.
                    Jika sentra ekonomi tersebut tidak bisa dicacah karena satu dan lain hal,
                    sentra ekonomi tersebut bisa ditandai <code>non target</code> oleh Garda Provinsi.
                    Pemberian status <code>non target</code> mengikuti hasil identifikasi pada <a target="_blank"
                        href="https://s.bps.go.id/mall_pertokoan">link https://s.bps.go.id/mall_pertokoan.</a>
                </div>
            </div>
            <div class="card-body pt-1">
                <div>
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
                            <label class="form-control-label">Status Target <span class="text-danger">*</span></label>
                            <select id="target" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Status Target -- </option>
                                @foreach ($targets as $target)
                                    <option value="{{ $target['value'] }}">
                                        {{ $target['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-control-label">Status Penyelesaian <span class="text-danger">*</span></label>
                            <select id="completion" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Status Penyelesaian -- </option>
                                @foreach ($completionStatus as $completion)
                                    <option value="{{ $completion['value'] }}">
                                        {{ $completion['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-control-label">Tipe <span class="text-danger">*</span></label>
                            <select id="marketType" class="form-control" data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Tipe -- </option>
                                @foreach ($marketTypes as $marketType)
                                    <option value="{{ $marketType->id }}">
                                        {{ $marketType->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <table id="myTable" class="align-items-center mb-0 text-sm">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Wilayah</th>
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Tipe</th>
                                @hasrole('adminprov|adminkab')
                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">Status Target</th>
                                @endhasrole
                                @hasrole('adminprov|adminkab')
                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">Status Penyelesaian</th>
                                @endhasrole
                                <th class="text-uppercase text-small font-weight-bolder opacity-7">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
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
            selector: '#target',
            placeholder: 'Pilih Status Target'
        }, {
            selector: '#completion',
            placeholder: 'Pilih Status Penyelesaian'
        }, {
            selector: '#marketType',
            placeholder: 'Pilih Tipe'
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
            '#target': () => {
                renderTable()
            },
            '#completion': () => {
                renderTable()
            },
            '#marketType': () => {
                renderTable()
            },
        };

        Object.entries(eventHandlers).forEach(([selector, handler]) => {
            $(selector).on('change', handler);
        });

        function refresh() {
            tableStatus.ajax.url('/status/data/1').load();
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
            filterTypes = ['role', 'organization', 'target', 'completion', 'marketType']
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            mytable.ajax.url('/pasar/manajemen/data?' + filterUrl).load();
        }

        function showToggleStatus(id, status, type) {
            const loading = document.getElementById(`${type}-loading-${id}`);
            const success = document.getElementById(`${type}-success-${id}`);
            const error = document.getElementById(`${type}-error-${id}`);

            // Hide all first
            loading.classList.add('d-none');
            success.classList.add('d-none');
            error.classList.add('d-none');

            // Show the current status icon
            if (status === 'loading') {
                loading.classList.remove('d-none');
            } else if (status === 'success') {
                success.classList.remove('d-none');
                setTimeout(() => showToggleStatus(id, null, type), 2000); // auto-hide
            } else if (status === 'error') {
                error.classList.remove('d-none');
                setTimeout(() => showToggleStatus(id, null, type), 2000); // auto-hide
            }
        }

        function toggleCategory(element, id) {
            const isChecked = element.checked;
            const label = document.getElementById(`category-${id}-label`);

            showToggleStatus(id, 'loading', 'category');
            fetch(`/pasar/manajemen/kategori/${id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        target_category: isChecked
                    })
                })
                .then(response => {
                    if (!response.ok) throw new Error("Request failed");
                    return response.json();
                })
                .then(data => {
                    label.textContent = isChecked ? 'Target' :
                        'Non Target';
                    showToggleStatus(id, 'success', 'category');
                })
                .catch(error => {
                    showToggleStatus(id, 'error', 'category');
                    element.checked = !isChecked;
                    label.textContent = !isChecked ? 'Target' : 'Non Target';
                });
        }

        function toggleCompletion(element, id) {
            const isChecked = element.checked;
            const label = document.getElementById(`completion-${id}-label`);
            const messageContainer = document.getElementById(`completion-message-${id}`);

            // Clear previous message
            messageContainer.classList.add('d-none');
            messageContainer.textContent = '';

            showToggleStatus(id, 'loading', 'completion');

            fetch(`/pasar/manajemen/selesai/${id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    },
                    body: JSON.stringify({
                        completion_status: isChecked
                    })
                })
                .then(async response => {
                    if (!response.ok) {
                        const errorData = await response.json();
                        throw errorData;
                    }
                    return response.json();
                })
                .then(data => {
                    label.textContent = data.completion_status;
                    showToggleStatus(id, 'success', 'completion');
                })
                .catch(error => {
                    showToggleStatus(id, 'error', 'completion');
                    element.checked = !isChecked;
                    label.textContent = error.completion_status;

                    // Show error message in table
                    messageContainer.textContent = error.message;
                    messageContainer.classList.remove('d-none');
                });
        }


        function deleteMarket(id, name) {
            event.preventDefault();
            Swal.fire({
                title: `Yakin Hapus Sentra Ekonomi Ini? ${name}`,
                text: 'Menghapus Sentra Ekonomi akan menghapus seluruh muatan pada Sentra Ekonomi tersebut',
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

        var isAdminProv = @json($isAdminProv);
        var isAdminKab = @json($isAdminKab);
        var allowedMarketTypes = @json($allowedMarketTypes);

        let mytable = new DataTable('#myTable', {
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
                                        <p class="text-sm text-secondary mb-0">${row.regency.name}, ${row.subdistrict.name}, ${data.name}</p>
                                    </div>
                                `
                        }
                        return data
                    }
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "market_type.name",
                    type: "text",
                },
                @hasrole('adminprov|adminkab')
                    {
                        responsivePriority: 1,
                        width: "10%",
                        data: "target_category",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                if (isAdminProv || (isAdminKab && allowedMarketTypes.includes(row
                                        .market_type_id))) {
                                
                                    var isCheckedInput = data == 'target' ? 'checked' : '';
                                    return `
                                        <div class="form-check form-switch">
                                            <input id="category-${row.id}-input" onchange="toggleCategory(this, '${row.id}')" style="height: 1.25rem !important" class="form-check-input" name="managedbyprov"
                                                type="checkbox" ${isCheckedInput}>
                                            <label id="category-${row.id}-label" class="form-check-label me-1" for="managedbyprov">${data.replace(/\b\w/g, char => char.toUpperCase())}</label>
                                            <span id="category-loading-${row.id}" class="d-none text-warning">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                            <span id="category-success-${row.id}" class="d-none text-success">
                                                <i class="fas fa-check-circle"></i>
                                            </span>
                                            <span id="category-error-${row.id}" class="d-none text-danger">
                                                <i class="fas fa-times-circle"></i>
                                            </span>
                                        </div>
                                `;
                                } else {
                                    if (data == 'target') {
                                        return '<span class="badge badge-sm bg-gradient-success">Target</span>';
                                    } else if (data == 'non target') {
                                        return '<span class="badge badge-sm bg-gradient-danger">Non Target</span>';
                                    } else {
                                        return data;
                                    }
                                }

                            }
                            return data;
                        }
                    },
                @endhasrole
                @hasrole('adminprov|adminkab')
                    {
                        responsivePriority: 1,
                        width: "10%",
                        data: "completion_status",
                        type: "text",
                        render: function(data, type, row) {
                            if (type === 'display') {
                                var isCheckedInput = data == 'done' ? 'checked' : '';
                                return `
            <div class="d-flex flex-column align-items-start">
                <div class="form-check form-switch">
                    <input id="completion-${row.id}-input" onchange="toggleCompletion(this, '${row.id}')" style="height: 1.25rem !important" class="form-check-input" name="managedbyprov"
                        type="checkbox" ${isCheckedInput}>
                    <label id="completion-${row.id}-label" class="form-check-label me-1" for="managedbyprov">
                        ${row.transformed_completion_status.replace(/\b\w/g, char => char.toUpperCase())}
                    </label>
                    <span id="completion-loading-${row.id}" class="d-none text-warning">
                        <i class="fas fa-spinner fa-spin"></i>
                    </span>
                    <span id="completion-success-${row.id}" class="d-none text-success">
                        <i class="fas fa-check-circle"></i>
                    </span>
                    <span id="completion-error-${row.id}" class="d-none text-danger">
                        <i class="fas fa-times-circle"></i>
                    </span>
                </div>
                <div id="completion-message-${row.id}" class="text-danger small mt-1 d-none"></div>
            </div>
        `;
                            }
                            return data;
                        }

                    },
                @endhasrole {
                    responsivePriority: 3,
                    width: "10%",
                    data: "id",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {

                            var editButton = isAdminProv || (isAdminKab && allowedMarketTypes.includes(
                                row.market_type_id)) ? `
                                        <a href="/pasar/manajemen/${data}/edit" class="px-2 py-1 m-0 btn btn-icon btn-outline-info btn-sm" role="button">
                                            <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
                                        </a>
                                ` : ''

                            var deleteButton = isAdminProv ? `
                                        <form class="d-inline" id="formdelete${data}" name="formdelete${data}" onSubmit="deleteMarket('${data}','${row.name}')" 
                                            method="POST" action="/pasar/manajemen/${data}">
                                            @csrf
                                            @method('DELETE')
                                            <button class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" type="submit">
                                                <span class="btn-inner--icon"><i class="fas fa-trash"></i></span>
                                            </button>
                                        </form>
                                ` : ''

                            return `
                                    <div class="d-flex align-items-center justify-content-start my-2 gap-2">
                                        <form class="d-inline" name="formdownload${data}" onSubmit="downloadUser('${data}','${row.name}')" 
                                            method="POST" action="/pasar/manajemen/download/${data}">
                                            @csrf
                                            <button class="px-2 py-1 m-0 btn btn-icon btn-outline-success btn-sm" type="submit">
                                                <span class="btn-inner--icon">
                                                    <i class="fas fa-download"></i>
                                                    Download Project
                                                </span>
                                            </button>
                                        </form>
                                        ${editButton}
                                        ${deleteButton}
                                    </div>
                                `
                        }
                        return data
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

        let tableStatus = new DataTable('#statusTable', {
            order: [],
            serverSide: true,
            processing: true,
            // deferLoading: 0,
            ajax: {
                url: '/status/data/1',
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
                            if (row.status == 'success') {
                                return `
                        <form class="my-2" action="/status/download/1" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="${data}"> 
                            <button class="btn btn-outline-secondary btn-sm ms-auto p-1 m-0" type="submit">
                                <i class="fas fa-download mx-1"></i>
                            </button>
                        </form>
                        `
                            } else {
                                return '-'
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
@endpush
