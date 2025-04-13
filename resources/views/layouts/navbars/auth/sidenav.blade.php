<aside class="sidenav bg-white navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-4 "
    id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-secondary opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="{{ route('home') }}" target="_blank">
            <img style="max-height: 3rem !important;" src="/img/short_logo.png" class="navbar-brand-img h-100"
                alt="main_logo">
            <img style="max-height: 3rem !important;" src="/img/text_logo.png" class="navbar-brand-img h-100"
                alt="main_logo">
        </a>
    </div>
    <hr class="horizontal dark mt-0">
    <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'home' ? 'active' : '' }}"
                    href="{{ route('home') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-home text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>
            @hasrole('pcl')
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Direktori</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'updating-sls' ? 'active' : '' }}"
                    href="{{ route('updating-sls') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-list-ol text-success text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Sampai Level SLS</span>
                </a>
            </li>
            @endhasrole
            @hasrole('pml|operator')
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Direktori</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'updating-non-sls' ? 'active' : '' }}"
                    href="{{ route('updating-non-sls') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-list-ul text-danger text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Tidak Sampai Level SLS</span>
                </a>
            </li>
            @endhasrole
            @hasrole('adminkab|adminprov')
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Direktori</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'updating-sls' ? 'active' : '' }}"
                    href="{{ route('updating-sls') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-list-ol text-success text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Sampai Level SLS</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'updating-non-sls' ? 'active' : '' }}"
                    href="{{ route('updating-non-sls') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-list-ul text-danger text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Tidak Sampai Level SLS</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'tambah-direktori' ? 'active' : '' }}"
                    href="{{ route('tambah-direktori') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-plus text-info text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Direktori Tambahan</span>
                </a>
            </li>
            @endhasrole
            @hasrole('adminkab')
            {{-- <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'assignment' ? 'active' : '' }}"
                    href="{{ route('assignment') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-file-lines text-success text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Assignment</span>
                </a>
            </li> --}}
            <li class="nav-item">
                <a class="nav-link {{ str_contains(request()->url(), 'download') == true ? 'active' : '' }}"
                    href="{{ route('download') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-file text-warning text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Unduh</span>
                </a>
            </li>
            @endhasrole
            @hasrole('pml|operator|adminkab|adminprov')
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Direktori Pasar</h6>
            </li>
            @hasrole('adminkab|adminprov')
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'market-dashboard' ? 'active' : '' }}"
                    href="{{ route('market-dashboard') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-table-columns text-primary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Proggress</span>
                </a>
            </li>
            @endhasrole
            @hasrole('adminkab|adminprov')
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'market-assignment' ? 'active' : '' }}"
                    href="{{ route('market-assignment') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-square-poll-horizontal text-info text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Assignment Pasar</span>
                </a>
            </li>
            @endhasrole
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'market' ? 'active' : '' }}"
                    href="{{ route('market') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-shop text-success text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Usaha</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Route::currentRouteName() == 'market-upload' ? 'active' : '' }}"
                    href="{{ route('market-upload') }}">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-store text-danger text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Upload</span>
                </a>
            </li>
            @endhasrole
            @hasrole('adminprov')
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Personifikasi</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ str_contains(request()->url(), 'personifikasi') == true ? 'active' : '' }}"
                    href="/personifikasi">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-user text-success text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Personifikasi</span>
                </a>
            </li>
            @endhasrole
            @hasrole('adminkab|adminprov')
            <li class="nav-item mt-3">
                <h6 class="ps-4 ms-2 text-uppercase text-xs font-weight-bolder opacity-6">Petugas</h6>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ str_contains(request()->url(), 'users') == true ? 'active' : '' }}"
                    href="/users">
                    <div
                        class="icon icon-shape icon-sm border-radius-md text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-user text-secondary text-sm opacity-10"></i>
                    </div>
                    <span class="nav-link-text ms-1">Petugas</span>
                </a>
            </li>
            @endhasrole
            @impersonating($guard = null)
            <li class="nav-item px-3 mt-2">
                <a href="{{ route('impersonate.leave') }}" class="px-2 py-1 m-0 btn btn-icon btn-outline-primary w-100"
                    role="button">
                    <span class="btn-inner--icon"><i class="fas fa-stop"></i></span>
                    <span class="btn-inner--text">Stop Personifikasi</span>
                </a>
            </li>
            <li class="nav-item px-3 mt-2">
                <p class="text-xs text-muted"><span>Personifikasi sbg:
                        <strong>{{ Auth::user()->firstname }}</strong></span></p>
            </li>
            @endImpersonating
        </ul>
    </div>
</aside>