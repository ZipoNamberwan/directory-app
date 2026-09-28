@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <link href="/vendor/tabulator/tabulator_bootstrap3.min.css" rel="stylesheet" />

    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Alokasi Petugas SE2026'])
    <div class="container-fluid py-4">
        <div class="card mt-2">
            <div class="card-header pb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="text-capitalize">Alokasi Petugas SE2026</h5>
                    <button type="button" class="btn btn-success mb-0 p-2" data-bs-toggle="modal"
                        data-bs-target="#manualAllocationModal">
                        <i class="fas fa-plus me-2"></i>Alokasi Manual
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-control-label">Kabupaten</label>
                        <select style="width: 100%;" id="regency" name="regency" class="form-control"
                            data-toggle="select">
                            <option value="0" disabled selected> -- Filter Kabupaten -- </option>
                            @foreach ($regencies as $regency)
                                <option value="{{ $regency->id }}">
                                    [{{ $regency->short_code }}] {{ $regency->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Kecamatan</label>
                        <select style="width: 100%;" id="subdistrict" name="subdistrict" class="form-control"
                            data-toggle="select">
                            <option value="0" disabled selected> -- Filter Kecamatan -- </option>
                            @foreach ($subdistricts as $subdistrict)
                                <option value="{{ $subdistrict->id }}">
                                    [{{ $subdistrict->short_code }}] {{ $subdistrict->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">Desa</label>
                        <select id="village" name="village" class="form-control" data-toggle="select"></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-control-label">SLS</label>
                        <select id="sls" name="sls" class="form-control" data-toggle="select"></select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-control-label" for="keyword">Cari Petugas</label>
                        <input type="text" class="form-control" id="keyword" placeholder="Cari Nama atau Email Petugas">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-12">
                        <p class="mb-2 text-muted small">
                            Jumlah alokasi yang difilter: <span id="total-records" class="fw-bold">0</span>
                        </p>
                    </div>
                </div>
                <div id="data-table"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="manualAllocationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Alokasi Manual</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="manualAllocationForm">
                        <div class="mb-3">
                            <label for="allocationEmail" class="form-label">Email Petugas</label>
                            <input type="email" class="form-control" id="allocationEmail" required>
                            <div class="invalid-feedback" id="allocationEmailError"></div>
                        </div>
                        <div class="mb-3">
                            <label for="allocationSlsLongCode" class="form-label">Kode SLS (Long Code)</label>
                            <input type="text" class="form-control" id="allocationSlsLongCode" required>
                            <div class="invalid-feedback" id="allocationSlsLongCodeError"></div>
                        </div>
                        <div class="alert alert-danger d-none" id="manualAllocationError"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="save-manual-allocation">
                        <span class="btn-text">Simpan</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
    <script src="/vendor/select2/select2.min.js"></script>
    <script src="/vendor/tabulator/tabulator.min.js"></script>

    <script>
        const selectConfigs = [{
                selector: '#regency',
                placeholder: 'Pilih Kabupaten'
            },
            {
                selector: '#subdistrict',
                placeholder: 'Pilih Kecamatan'
            },
            {
                selector: '#village',
                placeholder: 'Pilih Desa'
            },
            {
                selector: '#sls',
                placeholder: 'Pilih SLS'
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
            '#regency': () => {
                loadSubdistrict(null, null);
                renderTable()
            },
            '#subdistrict': () => {
                loadVillage(null, null);
                renderTable()
            },
            '#village': () => {
                loadSls(null, null);
                renderTable()
            },
            '#sls': () => {
                renderTable()
            },
        };

        function loadSubdistrict(regencyid = null, selectedvillage = null) {
            let regencySelector = `#regency`;
            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(regencySelector).val();
            if (regencyid != null) {
                id = regencyid;
            }

            $(subdistrictSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/kec/' + id,
                    success: function(response) {
                        $(subdistrictSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Kecamatan -- </option>`);
                        $(villageSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);

                        response.forEach(element => {
                            let selected = selectedvillage == String(element.id) ? 'selected' : '';
                            $(subdistrictSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(subdistrictSelector).empty().append(`<option value="0" disabled> -- Pilih Kecamatan -- </option>`);
                $(villageSelector).empty().append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

        function loadVillage(subdistrictid = null, selectedvillage = null) {
            let subdistrictSelector = `#subdistrict`;
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

            let id = $(subdistrictSelector).val();
            if (subdistrictid != null) {
                id = subdistrictid;
            }

            $(villageSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);
            $(slsSelector).empty().append(`<option value="0" disabled selected>Processing...</option>`);

            if (id != null) {
                $.ajax({
                    type: 'GET',
                    url: '/desa/' + id,
                    success: function(response) {
                        $(villageSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih Desa -- </option>`);
                        $(slsSelector).empty().append(
                            `<option value="0" disabled selected> -- Pilih SLS -- </option>`);
                        response.forEach(element => {
                            let selected = selectedvillage == String(element.id) ? 'selected' : '';
                            $(villageSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(villageSelector).empty().append(`<option value="0" disabled> -- Pilih Desa -- </option>`);
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

        function loadSls(villageid = null, selectedsls = null) {
            let villageSelector = `#village`;
            let slsSelector = `#sls`;

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
                            let selected = selectedsls == String(element.id) ? 'selected' : '';
                            $(slsSelector).append(
                                `<option value="${element.id}" ${selected}>[${element.short_code}] ${element.name}</option>`
                            );
                        });
                    }
                });
            } else {
                $(slsSelector).empty().append(`<option value="0" disabled> -- Pilih SLS -- </option>`);
            }
        }

        Object.entries(eventHandlers).forEach(([selector, handler]) => {
            $(selector).on('change', handler);
        });

        function toTitleCase(input) {
            const str = String(input);
            return str
                .toLowerCase()
                .split(/\s+/)
                .filter(Boolean)
                .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                .join(" ");
        }

        function getFilterUrl(filter) {
            var filterUrl = ''
            var e = document.getElementById(filter);
            if (e != null) {
                if (filter == 'keyword') {
                    filterUrl = `&${filter}=` + e.value
                } else {
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
            filterTypes = ['regency', 'subdistrict', 'village', 'sls', 'keyword'];
            filterTypes.forEach(f => {
                filterUrl += getFilterUrl(f)
            });

            table.setData('/se2026/allocation/data?' + filterUrl);
        }

        // debounce function
        function debounce(func, delay) {
            let timer;
            return function(...args) {
                clearTimeout(timer);
                timer = setTimeout(() => {
                    func.apply(this, args);
                }, delay);
            };
        }

        function handleSearch(e) {
            renderTable();
        }

        const input = document.getElementById("keyword");
        input.addEventListener("input", debounce(handleSearch, 500));

        let table;

        const columns = [{
                title: "Petugas",
                field: "user",
                formatter: function(cell) {
                    const user = cell.getValue();
                    const firstname = user?.firstname ?? "-";
                    const email = user?.email ?? "";
                    return `<div class="text-wrap">
                        <div class="fw-semibold">${firstname}</div>
                        ${email ? `<div class="small text-muted">${email}</div>` : ""}
                    </div>`;
                }
            },
            {
                title: "Wilayah",
                field: "sls",
                formatter: function(cell) {
                    let sls = cell.getValue();
                    if (!sls) return "-";

                    let areaNames = [];
                    if (sls.village?.subdistrict?.regency?.name) {
                        areaNames.push(sls.village.subdistrict.regency.name);
                    }
                    if (sls.village?.subdistrict?.name) {
                        areaNames.push(sls.village.subdistrict.name);
                    }
                    if (sls.village?.name) {
                        areaNames.push(sls.village.name);
                    }
                    if (sls.name) {
                        areaNames.push(sls.name);
                    }

                    return `<div class="text-wrap lh-sm">
                        <div class="fw-semibold text-success">${toTitleCase(sls.long_code)}</div>
                        <div class="small text-muted">${areaNames.join(", ")}</div>
                    </div>`;
                }
            },
        ];

        function initializeTable() {
            table = new Tabulator("#data-table", {
                height: "800px",
                layout: "fitColumns",
                ajaxURL: "/se2026/allocation/data",
                progressiveLoad: "scroll",
                paginationSize: 20,
                placeholder: "Tidak ada alokasi yang ditemukan",
                columns: columns,
                ajaxResponse: function(url, params, response) {
                    document.getElementById("total-records").textContent = response.total_records;
                    return response;
                },
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            initializeTable();
        });

        function clearManualAllocationErrors() {
            document.querySelectorAll('#manualAllocationForm .is-invalid').forEach(el => el.classList.remove(
                'is-invalid'));
            document.querySelectorAll('#manualAllocationForm .invalid-feedback').forEach(el => el.textContent = '');
            document.getElementById('manualAllocationError').classList.add('d-none');
        }

        document.getElementById('manualAllocationModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('manualAllocationForm').reset();
            clearManualAllocationErrors();
        });

        document.getElementById('save-manual-allocation').addEventListener('click', function() {
            const saveBtn = this;
            clearManualAllocationErrors();

            const email = document.getElementById('allocationEmail').value.trim();
            const slsLongCode = document.getElementById('allocationSlsLongCode').value.trim();

            saveBtn.disabled = true;
            saveBtn.querySelector('.btn-text').textContent = 'Menyimpan...';
            saveBtn.querySelector('.spinner-border').classList.remove('d-none');

            fetch('/se2026/allocation/manual', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    },
                    body: JSON.stringify({
                        email: email,
                        sls_long_code: slsLongCode
                    })
                })
                .then(response => response.json().then(data => ({
                    status: response.status,
                    data
                })))
                .then(({
                    status,
                    data
                }) => {
                    if (status === 422 && data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const fieldMapping = {
                                'email': 'allocationEmail',
                                'sls_long_code': 'allocationSlsLongCode'
                            };
                            const mappedField = fieldMapping[field];
                            if (mappedField) {
                                document.getElementById(mappedField).classList.add('is-invalid');
                                document.getElementById(mappedField + 'Error').textContent = data.errors[
                                    field][0];
                            }
                        });
                        return;
                    }

                    if (!data.success) {
                        const errorDiv = document.getElementById('manualAllocationError');
                        errorDiv.textContent = data.message || 'Terjadi kesalahan saat menyimpan alokasi';
                        errorDiv.classList.remove('d-none');
                        return;
                    }

                    bootstrap.Modal.getInstance(document.getElementById('manualAllocationModal')).hide();
                    renderTable();
                })
                .catch(error => {
                    console.error('Error:', error);
                    const errorDiv = document.getElementById('manualAllocationError');
                    errorDiv.textContent = 'Terjadi kesalahan sistem. Silakan coba lagi.';
                    errorDiv.classList.remove('d-none');
                })
                .finally(() => {
                    saveBtn.disabled = false;
                    saveBtn.querySelector('.btn-text').textContent = 'Simpan';
                    saveBtn.querySelector('.spinner-border').classList.add('d-none');
                });
        });
    </script>
@endpush
