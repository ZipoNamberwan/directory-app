@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/dataTables.bootstrap5.min.css" rel="stylesheet" />
    <link href="/vendor/datatables/responsive.bootstrap5.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .badge-type {
            font-size: 0.68rem;
            padding: 0.3em 0.65em;
            border-radius: 6px;
            font-weight: 600;
            letter-spacing: 0.02em;
        }
        .tag-pill {
            display: inline-block;
            background: #f0f2f5;
            color: #5e6e82;
            border-radius: 20px;
            padding: 1px 8px;
            font-size: 0.68rem;
            margin: 1px 2px 1px 0;
            white-space: nowrap;
        }
        .status-icon { font-size: 1rem; }
        #myTable td { vertical-align: middle !important; }
        .relative-time { font-size: 0.78rem; color: #344767; }
        .relative-time small { display: block; font-size: 0.68rem; color: #8392ab; }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Info & Pengumuman'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">

        @if (session('success-edit') || session('success-create'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="alert-icon"><i class="ni ni-like-2"></i></span>
                <span class="alert-text"><strong>Berhasil!</strong>
                    {{ session('success-create') }}{{ session('success-edit') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (session('success-delete'))
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <span class="alert-icon"><i class="ni ni-dislike-2"></i></span>
                <span class="alert-text">{{ session('success-delete') }}</span>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="mb-1">Info &amp; Pengumuman</h4>
                        <p class="text-sm text-secondary mb-0">Kelola informasi, pengumuman, dan FAQ yang ditampilkan kepada pengguna.</p>
                    </div>
                    <a href="/info/create" class="btn btn-primary btn-sm px-3" role="button">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </a>
                </div>
            </div>
            <div class="card-body pt-3">

                {{-- Filters – same UX as user page --}}
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-control-label">Tipe</label>
                        <select id="type" class="form-control" data-toggle="select">
                            <option value="0" disabled selected> -- Pilih Tipe -- </option>
                            <option value="announcement">Pengumuman</option>
                            <option value="faq">FAQ</option>
                            <option value="problem-solution">Kendala/Solusi</option>
                            <option value="other">Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Status Publikasi</label>
                        <select id="is_published" class="form-control" data-toggle="select">
                            <option value="" disabled selected> -- Pilih Status -- </option>
                            <option value="1">Dipublikasi</option>
                            <option value="0">Belum Dipublikasi</option>
                        </select>
                    </div>
                </div>

                <table id="myTable" class="align-items-center mb-0 text-sm w-100">
                    <thead>
                        <tr>
                            <th class="text-uppercase text-secondary font-weight-bolder opacity-7 ps-2">Judul</th>
                            <th class="text-uppercase text-secondary font-weight-bolder opacity-7">Tags</th>
                            <th class="text-uppercase text-secondary font-weight-bolder opacity-7">Tipe</th>
                            <th class="text-uppercase text-secondary font-weight-bolder opacity-7 text-center">Status</th>
                            <th class="text-uppercase text-secondary font-weight-bolder opacity-7">Diperbarui</th>
                            <th class="text-uppercase text-secondary font-weight-bolder opacity-7">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
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
        <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.30.1/moment-with-locales.min.js"></script>

        <script>
            moment.locale('id');

            const TYPE_CONFIG = {
                announcement:       { label: 'Pengumuman',     color: 'bg-primary'   },
                faq:                { label: 'FAQ',            color: 'bg-info'      },
                'problem-solution': { label: 'Kendala/Solusi', color: 'bg-warning'   },
                other:              { label: 'Lainnya',        color: 'bg-secondary' },
            };

            // ─── Select2 filters (same pattern as user page) ─────────────────
            const selectConfigs = [
                { selector: '#type',         placeholder: 'Pilih Tipe'   },
                { selector: '#is_published', placeholder: 'Pilih Status' },
            ];

            selectConfigs.forEach(({ selector, placeholder }) => {
                $(selector).select2({ placeholder, allowClear: true });
            });

            const eventHandlers = {
                '#type':         () => renderTable(),
                '#is_published': () => renderTable(),
            };

            Object.entries(eventHandlers).forEach(([selector, handler]) => {
                $(selector).on('change', handler);
            });

            function getFilterUrl(filter) {
                const e = document.getElementById(filter);
                if (!e) return '';
                const selected = e.options[e.selectedIndex];
                if (!selected) return '';
                const val = selected.value;
                if (val === '0' || val === '') return '';
                return `&${filter}=${val}`;
            }

            function renderTable() {
                let filterUrl = '';
                ['type', 'is_published'].forEach(f => { filterUrl += getFilterUrl(f); });
                table.ajax.url('/info/data?' + filterUrl).load();
            }

            // ─── Helpers ─────────────────────────────────────────────────────
            function renderTags(raw) {
                if (!raw) return '<span class="text-muted">—</span>';
                return raw.split(',')
                    .map(t => t.trim()).filter(Boolean)
                    .map(t => `<span class="tag-pill">${t}</span>`)
                    .join('');
            }

            // ─── DataTable ───────────────────────────────────────────────────
            let table = new DataTable('#myTable', {
                order: [[4, 'desc']],
                serverSide: true,
                processing: true,
                ajax: { url: '/info/data', type: 'GET' },
                responsive: true,
                columns: [
                    // 0 – Judul + Subtitle
                    {
                        data: 'title', width: '30%', responsivePriority: 1,
                        render(data, type, row) {
                            if (type !== 'display') return data;
                            const sub = row.subtitle
                                ? `<p class="text-xs text-secondary mb-0 mt-1">${row.subtitle}</p>`
                                : '';
                            return `<div class="my-1"><span class="fw-bold">${data}</span>${sub}</div>`;
                        }
                    },
                    // 1 – Tags
                    {
                        data: 'tags', width: '18%', responsivePriority: 3,
                        render(data, type) {
                            if (type !== 'display') return data ?? '';
                            return renderTags(data);
                        }
                    },
                    // 2 – Tipe
                    {
                        data: 'type', width: '10%', responsivePriority: 2,
                        render(data, type) {
                            if (type !== 'display') return data;
                            const cfg = TYPE_CONFIG[data] ?? { label: data, color: 'bg-secondary' };
                            return `<span class="badge badge-type ${cfg.color} text-white">${cfg.label}</span>`;
                        }
                    },
                    // 3 – Status
                    {
                        data: 'is_published', width: '7%', responsivePriority: 2,
                        className: 'text-center',
                        render(data, type) {
                            if (type !== 'display') return data;
                            return data
                                ? `<i class="fas fa-check-circle status-icon text-success" title="Dipublikasi"></i>`
                                : `<i class="fas fa-times-circle status-icon text-danger"  title="Tidak Dipublikasi"></i>`;
                        }
                    },
                    // 4 – Updated At (moment relative)
                    {
                        data: 'updated_at', width: '12%', responsivePriority: 3,
                        render(data, type) {
                            if (type !== 'display') return data ?? '';
                            if (!data) return '—';
                            const m = moment(data);
                            return `<span class="relative-time" title="${m.format('DD MMM YYYY, HH:mm')}">${m.fromNow()}</span>`;
                        }
                    },
                    // 5 – Aksi
                    {
                        data: 'id', width: '8%', orderable: false, responsivePriority: 1,
                        className: 'align-middle',
                        render(data, type, row) {
                            if (type !== 'display') return data;
                            const title = row.title.replace(/'/g, "\\'");
                            return `
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <a href="/info/${data}/edit"
                                       class="btn btn-icon btn-outline-info btn-sm px-2 py-1 mb-0" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form id="del${data}" method="POST" action="/info/${data}" class="d-inline"
                                          onsubmit="confirmDelete(event,'${data}','${title}')">
                                        @method('delete')
                                        @csrf
                                        <button class="btn btn-icon btn-outline-danger btn-sm px-2 py-1 mb-0" type="submit" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>`;
                        }
                    },
                ],
                language: {
                    paginate: {
                        previous: '<i class="fas fa-angle-left"></i>',
                        next:     '<i class="fas fa-angle-right"></i>'
                    },
                    processing: '<div class="py-3 text-center"><div class="spinner-border spinner-border-sm text-primary"></div></div>',
                }
            });

            function confirmDelete(e, id, title) {
                e.preventDefault();
                Swal.fire({
                    title: 'Hapus Info?',
                    html: `<span class="text-sm">"<strong>${title}</strong>" akan dihapus.</span>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74c3c',
                    cancelButtonColor: '#adb5bd',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal',
                }).then(r => { if (r.isConfirmed) document.getElementById('del' + id).submit(); });
            }
        </script>
    @endpush
@endsection
