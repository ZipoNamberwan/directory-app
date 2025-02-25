@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Direktori Tambahan'])
    <div class="container-fluid py-4">

        <div class="row">
            <div class="col-lg-12 mb-lg-0 mb-4">
                <div class="card z-index-2 h-100">
                    <div class="card-header pb-0 pt-3 bg-transparent">
                        <h6 class="text-capitalize">Direktori Tambahan</h6>
                        <p class="text-sm mb-0">
                            <span>Menu ini digunakan untuk melakukan upload <strong>direktori usaha tambahan</strong></span>
                        </p>
                    </div>
                    <div class="card-body p-3">

                        <div class="row">
                            <div class="col-md-6 col-sm-12 p-2">
                                <div class="bg-light p-4 rounded">
                                    <h6 class="mb-3 d-flex align-items-center">
                                        <span class="badge bg-info me-2">1</span>
                                        Unduh Template
                                    </h6>
                                    <p class="text-muted small mb-3">
                                        Unduh template yang telah disediakan. Template ini digunakan untuk mengisi direktori
                                        usaha tambahan.
                                    </p>
                                    <a href="{{ asset('template.xlsx') }}" download class="btn btn-info">
                                        <i class="fas fa-download me-2"></i> Unduh
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6 col-sm-12 p-2">
                                <div class="bg-light p-4 rounded">
                                    <h6 class="mb-3 d-flex align-items-center">
                                        <span class="badge bg-success me-2">2</span>
                                        Upload Direktori Usaha Tambahan
                                    </h6>
                                    <p class="text-muted small mb-3">
                                        Menu ini digunakan untuk mengupload template yang telah diisi. Proses ini akan
                                        melalui antrian dan memakan beberapa waktu.
                                    </p>
                                    <p class="text-muted small mb-3">
                                        Tombol status digunakan untuk melihat status upload.
                                    </p>
                                    @livewire('import-additional')
                                </div>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="mb-3 d-flex align-items-center">
                                            <span class="badge bg-danger me-2">3</span>
                                            Daftar Direktori Usaha Tambahan
                                        </h6>
                                        <table id="myTable" class="align-items-center mb-0 text-sm">
                                            <thead>
                                                <tr>
                                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">
                                                        Nama Usaha</th>
                                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">
                                                        Wilayah</th>
                                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">
                                                        Status</th>
                                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">
                                                        Terakhir Diupdate Oleh</th>
                                                    <th class="text-uppercase text-small font-weight-bolder opacity-7">
                                                        Sumber</th>
                                                    {{-- <th class="text-uppercase text-small font-weight-bolder opacity-7">
                                                        Aksi</th> --}}
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
                </div>
            </div>
        </div>

        <div class="modal fade" id="statusDialog" tabindex="-1" role="dialog" aria-labelledby="statusDialogLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                @livewire('status-dialog')
            </div>
        </div>

        @include('layouts.footers.auth.footer')
    </div>
@endsection

@push('js')
    <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>

    <script src="/vendor/datatables/dataTables.min.js"></script>
    <script src="/vendor/datatables/dataTables.bootstrap5.min.js"></script>

    <script src="/vendor/datatables/responsive.bootstrap5.min.js"></script>
    <script src="/vendor/datatables/dataTables.responsive.min.js"></script>

    <script>
        function escapeJsonForHtml(jsonString) {
            return jsonString
                .replace(/"/g, '&quot;') // Escape double quotes
                .replace(/'/g, '&#39;'); // Escape single quotes
        }

        function getLocationDetails(row) {
            let long_code = row.regency.long_code;
            let subdistrict = row.subdistrict ? row.subdistrict.name : '';
            let village = row.village ? row.village.name : '';
            let sls = row.sls ? row.sls.name : '';

            if (row.subdistrict) long_code += row.subdistrict.short_code;
            if (row.village) long_code += row.village.short_code;
            if (row.sls) long_code += row.sls.short_code;

            return {
                long_code,
                subdistrict,
                village,
                sls
            };
        }

        let table = new DataTable('#myTable', {
            order: [],
            serverSide: true,
            processing: true,
            ajax: {
                url: '/non-sls-directory/data?status=90&level=village',
                type: 'GET',
            },
            responsive: true,
            columns: [{
                    responsivePriority: 1,
                    width: "10%",
                    data: "name",
                    type: "text",
                    render: function(data, type, row) {
                        var areaDetail = getLocationDetails(row)
                        if (type === 'display') {
                            return data + (row.owner ? ' (' + row.owner + ')' : '');
                        }
                        return data.id
                    }
                },
                {
                    responsivePriority: 2,
                    width: "10%",
                    data: "sls",
                    type: "text",
                    render: function(data, type, row) {
                        var areaDetail = getLocationDetails(row)

                        if (type === 'display') {
                            return `<div class="my-1"> 
                                            <p style='font-size: 0.7rem' class='text-secondary mb-0'>${areaDetail.long_code}</p>
                                            <p style='font-size: 0.7rem' class='text-secondary mb-0'>${areaDetail.subdistrict}</p>
                                            <p style='font-size: 0.7rem' class='text-secondary mb-0'>${areaDetail.village}</p>
                                            <p style='font-size: 0.7rem' class='text-secondary mb-0'>${areaDetail.sls}</p>
                                        </div>`
                        }
                        return data.id
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
                        return data.id;
                    }
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "modified_by",
                    type: "text",
                    render: function(data, type, row) {
                        if (type === 'display') {
                            if (data == null) {
                                return `<p style='font-size: 0.7rem' class='text-secondary mb-0'>-</p>`;
                            } else {
                                return `<p style='font-size: 0.7rem' class='text-secondary mb-0'>${data.firstname}</p>`;
                            }
                        }
                        return data.id;
                    }
                },
                {
                    responsivePriority: 4,
                    width: "10%",
                    data: "source",
                    type: "text",
                },
                // {
                //     responsivePriority: 4,
                //     width: "10%",
                //     data: "id",
                //     type: "text",
                //     render: function(data, type, row) {
                //         if (type === 'display') {
                //             let newButton = ''
                //             if (row.is_new) {
                //                 newButton = `
            //                     <button data-row='${escapeJsonForHtml(JSON.stringify(row))}' onclick="onDeleteModal(this)" class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" type="button">
            //                         <span class="btn-inner--icon"><i class="fas fa-trash-alt"></i></span>
            //                     </button>`
                //             }
                //             return `<button data-row='${escapeJsonForHtml(JSON.stringify(row))}' onclick="openUpdateDirectoryModal(this)" class="px-2 py-1 m-0 btn btn-icon btn-outline-primary btn-sm" type="button">
            //                             <span class="btn-inner--icon"><i class="fas fa-edit"></i></span>
            //                         </button>
            //                         ${newButton}
            //                 `
                //         }
                //         return data;
                //     }
                // },
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
