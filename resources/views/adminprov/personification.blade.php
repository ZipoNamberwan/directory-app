@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Petugas'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">

        <div class="card">
            <div class="card-header pb-0">
                <h4 class="text-capitalize">Menu Personifikasi</h4>
                <p class="text-sm text-muted mb-0">Menu ini digunakan untuk melakukan login sebagai user yang lain</p>
            </div>
            <div class="card-body">
                <div>
                    <div class="row mb-3">
                        <div class="col-md-6 col-sm-12">
                            <label class="form-control-label" for="search">Ketik nama atau email <span
                                    class="text-danger">*</span></label>
                            <input type="text" name="search" class="form-control @error('search') is-invalid @enderror"
                                id="search" placeholder="Cari user di sini">
                        </div>
                    </div>
                    <div id="usersList" class="row"></div>
                </div>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script>
            function debounce(func, delay) {
                let timer;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => func.apply(this, args), delay);
                };
            }

            async function fetchUsers(query) {
                if (!query.trim()) {
                    document.getElementById("results").innerHTML = "";
                    return;
                }

                const resultDiv = document.getElementById('usersList');
                resultDiv.innerHTML = '<p class="text-warning text-sm">Loading<p/>';

                try {
                    const response = await fetch(`/users/search?search=${encodeURIComponent(query)}`);
                    const users = await response.json();

                    resultDiv.innerHTML = '';

                    users.forEach(user => {

                        const itemDiv = document.createElement('div');
                        itemDiv.className = 'col-md-4 col-sm-6 col-xs-12 p-1';
                        itemDiv.style = "cursor: pointer;"

                        itemDiv.innerHTML = `
                        <div class="border d-flex justify-content-between align-items-center px-3 py-2 border-radius-md">
                            <div>
                                <p style="font-size: 0.875rem;" class="mb-1">${user.firstname}</p>
                                <p style="font-size: 0.875rem;" class="mb-1">${user.email}</p>
                            </div>
                            <a href="/impersonate/${user.id}" class="px-2 py-1 m-0 btn btn-icon btn-outline-danger btn-sm" role="button">
                                <span class="btn-inner--icon"><i class="fas fa-arrow-pointer"></i></span>
                            </a>
                        </div>
                    `

                        resultDiv.appendChild(itemDiv);
                    });

                    if (response.length == 0) {
                        resultDiv.innerHTML = `<p class="text-small text-warning">Tidak ada direktori di SLS ini</p>`
                    }
                } catch (error) {
                    console.error('Error fetching users:', error);
                }
            }

            const searchInput = document.getElementById("search");
            searchInput.addEventListener("input", debounce((event) => {
                fetchUsers(event.target.value);
            }, 500));
        </script>
    @endpush
@endsection
