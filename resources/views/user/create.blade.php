@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Petugas'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex align-items-center">
                    <h4 class="text-capitalize">{{ $user == null ? 'Tambah Petugas' : 'Edit Petugas' }}</h4>
                </div>
            </div>
            <div class="card-body pt-1">
                <form id="formupdate" autocomplete="off" method="post"
                    action="{{ $user == null ? '/users' : '/users/' . $user->id }}" class="needs-validation"
                    enctype="multipart/form-data" novalidate>
                    @csrf

                    @if ($user != null)
                        @method('PUT')
                    @else
                        @method('POST')
                    @endif

                    <div class="row">
                        <div class="col-md-6 mt-3">
                            <label class="form-control-label" for="firstname">Nama <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="firstname"
                                class="form-control @error('firstname') is-invalid @enderror"
                                value="{{ @old('firstname', $user != null ? $user->firstname : '') }}" id="firstname"
                                placeholder="Nama Lengkap">
                            @error('firstname')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mt-3">
                            <label class="form-control-label" for="email">Email <span
                                    class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                placeholder="Email" value="{{ @old('email', $user != null ? $user->email : '') }}"
                                autocomplete="off">
                            @error('email')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mt-3">
                            <label class="form-control-label" for="pml">Role <span class="text-danger">*</span></label>
                            <div class="d-flex flex-column gap-2">
                                @hasrole('adminprov')
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" value="adminprov" name="role"
                                            id="customRadio4"
                                            {{ old('role', $user != null ? $user->hasRole('adminprov') : '') == 'adminprov' ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="customRadio4">Admin Prov</label>
                                    </div>
                                @endhasrole
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="adminkab" name="role"
                                        id="customRadio1"
                                        {{ old('role', $user != null ? $user->hasRole('adminkab') : '') == 'adminkab' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="customRadio1">Admin Kab</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="operator" name="role"
                                        id="customRadio2"
                                        {{ old('role', $user != null ? $user->hasRole('operator') : '') == 'operator' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="customRadio2">Operator/Organik</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="pml" name="role"
                                        id="customRadio5"
                                        {{ old('role', $user != null ? $user->hasRole('pml') : '') == 'pml' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="customRadio5">PML</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" value="pcl" name="role"
                                        id="customRadio3"
                                        {{ old('role', $user != null ? $user->hasRole('pcl') : '') == 'pcl' ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="customRadio3">PCL</label>
                                </div>
                            </div>
                            @error('role')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mt-3">
                            <label class="form-control-label" for="address">Apakah Petugas Wilkerstat?</label>
                            <input type="hidden" name="is_wilkerstat_user" value="0">
                            <div class="form-check form-switch">
                                <input value="1" onchange="toggleLabel()" class="form-check-input"
                                    name="is_wilkerstat_user" type="checkbox" id="is_wilkerstat_user"
                                    {{ old('is_wilkerstat_user', ($user->is_wilkerstat_user ?? 0) == 1) ? 'checked' : '' }}>
                                <label id="switchlabel" class="form-check-label" for="is_wilkerstat_user">Tidak</label>
                            </div>
                        </div>
                    </div>
                    @hasrole('adminprov')
                        <div class="row">
                            <div class="col-md-6 mt-3">
                                <label class="form-control-label">Satker <span class="text-danger">*</span></label>
                                <select style="width: 100%;" id="organization" name="organization" class="form-control"
                                    data-toggle="select">
                                    <option value="0" disabled selected> -- Pilih Satker -- </option>
                                    @foreach ($organizations as $organization)
                                        <option value="{{ $organization->id }}"
                                            {{ old('organization', $user != null ? ($user->organization != null ? $user->organization->id : null) : null) == $organization->id ? 'selected' : '' }}>
                                            [{{ $organization->short_code }}] {{ $organization->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('organization')
                                    <div class="error-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    @endhasrole
                    <div class="row">
                        <div class="col-md-6 mt-3 mb-3">
                            <label class="form-control-label" for="password">Password <span
                                    class="text-danger">*</span></label>
                            <input type="password" name="password"
                                class="form-control @error('password') is-invalid @enderror" id="password"
                                value="{{ @old('password', $user != null ? $user->password : '') }}"
                                autocomplete="new-password" placeholder="Password">
                            @error('password')
                                <div class="error-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" id="submit" type="submit">Submit</button>
                </form>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>

        <script>
            [{
                selector: '#organization',
                placeholder: 'Pilih Satker',
            }, ].forEach(config => {
                $(config.selector).select2({
                    placeholder: config.placeholder,
                    allowClear: true,
                });
            });
        </script>

        <script>
            function toggleLabel() {
                const checkbox = document.getElementById('is_wilkerstat_user');
                const label = document.getElementById('switchlabel');

                if (checkbox.checked) {
                    label.textContent = 'Ya';
                } else {
                    label.textContent = 'Tidak';
                }
            }
            toggleLabel()
        </script>
    @endpush
@endsection
