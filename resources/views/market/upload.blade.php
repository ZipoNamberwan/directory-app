@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Upload Direktori Pasar'])
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
                <h6 class="text-capitalize">Upload Direktori Pasar</h6>
                {{-- <p class="text-sm mb-0">
                    <span>Berikut rekap semua assignment direktori usaha</span>
                </p> --}}
            </div>
            <div class="card-body p-3">
                <form id="formupdate" autocomplete="off" method="post" action="/pasar/upload" class="needs-validation"
                    enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="row">
                        <div class="col-md-6 mt-3">
                            <label class="form-control-label">Pilih Pasar <span class="text-danger">*</span></label>
                            <select style="width: 100%;" id="market" name="market" class="form-control"
                                data-toggle="select">
                                <option value="0" disabled selected> -- Pilih Pasar -- </option>
                                @foreach ($markets as $market)
                                    <option value="{{ $market->id }}"
                                        {{ old('market') == $market->id ? 'selected' : '' }}>
                                        {{ $market->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('market')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mt-3">
                            <label class="form-control-label">File Hasil SW Maps <span class="text-danger">*</span></label>
                            <input id="file" name="file" type="file" class="form-control" accept=".xlsx,.csv">
                        </div>
                        @error('file')
                            <div class="error-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                    <button class="btn btn-primary mt-3" id="submit" type="submit">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mt-2">
            <div class="card-header pb-0">
                <h6 class="text-capitalize">Status Upload</h6>
            </div>
            <div class="card-body">
                <div>
                    <button id="refresh" onclick="renderTable()" class="btn btn-primary btn-sm p-2">Refresh</button>
                </div>
                <table id="myTable" class="align-items-center mb-0 text-sm">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Pasar</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">User</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">File</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Diupload Pada</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Status</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Pesan</th>
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

    <script>
        [{
            selector: '#market',
            placeholder: 'Pilih Pasar'
        }, ].forEach(config => {
            $(config.selector).select2({
                placeholder: config.placeholder,
                allowClear: true,
            });
        });
    </script>

    <script>
        function showFullMessage(id, fullText) {
            document.getElementById(id + '_short').style.display = 'none';
            document.getElementById(id + '_full').style.display = 'inline';
        }

        function showLessMessage(id) {
            document.getElementById(id + '_short').style.display = 'inline';
            document.getElementById(id + '_full').style.display = 'none';
        }
    </script>

    <script>
        function renderTable() {
            table.ajax.url('/pasar/upload/data').load();
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

        let table = new DataTable('#myTable', {
            order: [],
            serverSide: true,
            processing: true,
            // deferLoading: 0,
            ajax: {
                url: '/pasar/upload/data',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "market_name",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return `<div class="my-1"> 
                                    <p style='font-size: 0.875rem' class='text-secondary mb-0'>[${row.regency_id}] ${row.regency_name}</p>
                                    <p style='font-size: 0.875rem' class='text-secondary mb-0 text-bold'>${data}</p>
                                </div>`
                        }
                        return data
                    }
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
                        <form class="my-2" action="/pasar/download/swmap" method="POST">
                            @csrf
                            <input type="hidden" name="id" value="${data}"> 
                            <button class="btn btn-outline-secondary btn-sm ms-auto p-1 m-0" type="submit">
                                <i class="fas fa-download mx-1"></i>
                            </button>
                        </form>
                        `;
                        }
                        return data;
                    }
                },
                {
                    responsivePriority: 4,
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

                            return '<p class="mb-0"><span class="badge bg-gradient-' + color + '">' +
                                data + '</span></p>';
                        }
                        return data;
                    }
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "message",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            if (!data) return '-';

                            const id = 'msg_' + row.id; // unique ID for each row
                            const maxLength = 200;

                            if (data.length <= maxLength) {
                                return data;
                            }

                            const shortText = data.substring(0, maxLength) + '... ';
                            const html = `
                                <span id="${id}_short">${shortText}<a class="text-info" href="#" onclick="showFullMessage('${id}', \`${data.replace(/`/g, '\\`')}\`); return false;">Selengkapnya</a></span>
                                <span id="${id}_full" style="display:none;">${data} <a class="text-info" href="#" onclick="showLessMessage('${id}'); return false;">Baca lebih sedikit</a></span>
                            `;

                            return html;
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
