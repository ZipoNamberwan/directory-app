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
        <div class="card z-index-2 h-100">
            <div class="card-header pb-0 pt-3 bg-transparent">
                <h6 class="text-capitalize">Upload Direktori Pasar</h6>
                {{-- <p class="text-sm mb-0">
                    <span>Berikut rekap semua assignment direktori usaha</span>
                </p> --}}
            </div>
            <div class="card-body p-3">
                <form id="formupdate" autocomplete="off" method="post" action="/market" class="needs-validation"
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
                                        {{ old('market', $user != null ? $user->market->id : null) == $market->id ? 'selected' : '' }}>
                                        [{{ $market->short_code }}] {{ $market->name }}
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
                    </div>
                    <button class="btn btn-primary mt-3" id="submit" type="submit">Submit</button>
                </form>
            </div>
        </div>

        <div class="card mt-2">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h6 class="text-capitalize">Status Upload</h6>
                    <button id="refresh" onclick="refresh()"
                        class="btn btn-primary btn-sm ms-auto p-2 m-0">Refresh</button>
                </div>
            </div>
            <div class="card-body">
                <table id="myTable" class="align-items-center mb-0 text-sm">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Nama Pasar</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">User</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">File</th>
                            <th class="text-uppercase text-small font-weight-bolder opacity-7">Diupload Pada</th>
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
        let table = new DataTable('#myTable', {
            order: [],
            serverSide: true,
            processing: true,
            deferLoading: 0,
            ajax: {
                url: '/pasar-upload/data',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "market",
                    type: "text",
                    render: function(data, type, row) {
                        return data.name
                    }
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "user",
                    type: "text",
                    render: function(data, type, row) {
                        return data.firstname
                    }
                },
                {
                    responsivePriority: 3,
                    width: "10%",
                    data: "filename",
                    type: "text",
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "created_by",
                    type: "text",
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "id",
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
