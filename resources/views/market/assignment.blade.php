@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Assignment Sentra Ekonomi'])
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
                <h4 class="text-capitalize">Assignment Sentra Ekonomi</h4>
                <p class="text-sm mb-0">
                    <span>Menu ini digunakan untuk melakukan assignment <strong>sentra ekonomi dengan petugas.</strong></span>
                </p>
            </div>
            <div class="card-body p-3">
                <div class="border p-3 mb-3">
                    <h6>* Assignment Manual</h6>
                    <p class="text-sm">
                        <span>Gunakan menu ini untuk melakukan assignment satu per satu</span>
                    </p>
                    <a href="/pasar-assignment/create" class="btn btn-primary mb-0 p-2">Tambah</a>
                </div>
                <div class="border p-3">
                    <h6>* Assignment Menggunakan Template</h6>
                    <p class="text-sm">
                        <span>Gunakan menu ini untuk melakukan assignment sekaligus. Proses download template dan upload
                            assignment akan
                            melalui proses antrian. Sehingga data assignment mungkin akan diproses beberapa waktu setelah
                            upload</span>
                    </p>
                    <div class="row">
                        <div class="col-md-6 col-sm-12 p-2">
                            <div class="bg-light p-4 rounded">
                                <h6 class="mb-3 d-flex align-items-center">
                                    <span class="badge bg-info me-2">1</span>
                                    Download Template
                                </h6>
                                <p class="text-muted small mb-3">
                                    Menu ini digunakan untuk mengunduh template assignment sentra ekonomi.
                                </p>
                                <form id="formupdate" autocomplete="off" method="post" action="/pasar-assignment/download"
                                    class="needs-validation" enctype="multipart/form-data" novalidate>
                                    @csrf
                                    <button class="btn btn-info mt-3" id="submit" type="submit">Download</button>
                                </form>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12 p-2">
                            <div class="bg-light p-4 rounded">
                                <h6 class="mb-3 d-flex align-items-center">
                                    <span class="badge bg-success me-2">2</span>
                                    Upload
                                </h6>
                                <p class="text-muted small mb-3">
                                    Menu ini digunakan untuk mengupload template yang telah diisi. Karena data assignment
                                    yang
                                    diproses banyak, sehingga proses ini akan memakan beberapa waktu.
                                </p>
                                <p class="text-muted small mb-3">
                                    Tombol status digunakan untuk melihat status assignment.
                                </p>
                                <form id="formupdate" autocomplete="off" method="post" action="/pasar-assignment/upload"
                                    class="needs-validation" enctype="multipart/form-data" novalidate>
                                    @csrf
                                    <label class="form-control-label">File Assignment <span
                                            class="text-danger">*</span></label>
                                    <input id="file" name="file" type="file" class="form-control" accept=".xlsx">
                                    @error('file')
                                        <div class="error-feedback">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                    <button class="btn btn-success mt-3" id="submit" type="submit">Upload</button>
                                </form>

                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="row mt-3">
            <div class="col-md-8 col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6 class="mb-3 d-flex align-items-center">
                            <span class="badge bg-primary me-2">3</span>
                            Status Upload Assignment
                        </h6>
                        <p class="text-muted small mb-3">
                            Menu ini digunakan untuk monitor status upload assignment.
                            Gunakan tombol refresh untuk memperbarui status. <br>
                            Untuk melihat daftar assignment ada <a href="/pasar-assignment/list">di sini</a>.
                        </p>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <button onclick="renderStatus()" class="btn btn-primary p-2 mb-0 me-2">Refresh</button>
                            <a href="/pasar-assignment/list" class="btn btn-outline-primary mb-0 p-2">Daftar Assignment</a>
                        </div>
                        <table id="statusTable" class="align-items-center mb-0 text-sm">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">User</th>
                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">File yang Diupload
                                    </th>
                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">Diupload Pada
                                    </th>
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
            <div class="col-md-4 col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="text-capitalize">Daftar Assignment</h6>
                            <div>
                                <button onclick="renderPivot()" class="btn btn-primary p-2">Refresh</button>
                                <a href="/pasar-assignment/list" class="btn btn-outline-primary p-2">Selengkapnya</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        {{-- <div>
                            <button id="refresh" onclick="renderPivot()" class="btn btn-primary p-2">Refresh</button>
                        </div> --}}
                        <table id="myTable" class="align-items-center mb-0 text-sm">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Sentra Ekonomi</th>
                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama User</th>
                                    {{-- <th class="text-uppercase text-small font-weight-bolder opacity-7">Aksi</th> --}}
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
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
        function renderPivot() {
            table.ajax.url('/pasar-assignment/pivot').load();
        }

        function renderStatus() {
            table2.ajax.url('/status/data/4').load();
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
                // {
                //     responsivePriority: 3,
                //     width: "10%",
                //     data: "id",
                //     type: "text",
                // },
            ],
            language: {
                paginate: {
                    previous: '<i class="fas fa-angle-left"></i>',
                    next: '<i class="fas fa-angle-right"></i>'
                }
            }
        });


        let table2 = new DataTable('#statusTable', {
            order: [],
            serverSide: true,
            processing: true,
            // deferLoading: 0,
            ajax: {
                url: '/status/data/4',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "user",
                    type: "text",
                    render: function(data, type, row) {
                        return data.firstname;
                    }
                },
                {
                    responsivePriority: 2,
                    width: "5%",
                    data: "id",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            return `
                        <form class="my-2" action="/status/download/4" method="POST">
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
                    responsivePriority: 3,
                    width: "5%",
                    data: "created_at",
                    type: "text",
                    render: function(data, type, row) {
                        return formatDate(data)
                    }

                },
                {
                    responsivePriority: 3,
                    width: "5%",
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
                    width: "25%",
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
