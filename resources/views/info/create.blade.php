@extends('layouts.app', ['class' => 'g-sidenav-show bg-gray-100'])

@section('css')
    <link href="/assets/css/app.css" rel="stylesheet" />
    <link href="/vendor/select2/select2.min.css" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .form-section {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1.25rem 1.5rem;
            margin-top: 1rem;
        }
        .form-section-title {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #8392ab;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid #f0f2f5;
        }
        .form-control-label { font-size: 0.8rem; font-weight: 600; color: #344767; }
        textarea.form-control { resize: vertical; }
        .publish-toggle-wrap { display: flex; align-items: center; gap: 0.75rem; }
        .status-badge-preview { font-size: 0.75rem; font-weight: 600; padding: 0.25em 0.7em; border-radius: 20px; }
    </style>
@endsection

@section('content')
    @include('layouts.navbars.auth.topnav', ['title' => 'Info & Pengumuman'])
    <div class="full-screen-bg"></div>

    <div class="container-fluid">
        <div class="card">
            <div class="card-header pb-0 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="mb-0">{{ $info ? 'Edit Info' : 'Tambah Info' }}</h5>
                    <p class="text-sm text-secondary mb-0">
                        {{ $info ? 'Perbarui informasi yang sudah ada.' : 'Buat entri informasi baru.' }}
                    </p>
                </div>
                <a href="/info" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <div class="card-body pt-2">
                <form id="forminfo" method="post"
                      action="{{ $info ? '/info/' . $info->id : '/info' }}"
                      enctype="multipart/form-data" novalidate>
                    @csrf
                    @if ($info)
                        @method('PUT')
                    @endif

                    {{-- ─── Section 1: Umum ────────────────────────────────────── --}}
                    <div class="form-section">
                        <p class="form-section-title"><i class="fas fa-tag me-1"></i> Informasi Umum</p>

                        <div class="row g-3">
                            {{-- Judul --}}
                            <div class="col-md-8">
                                <label class="form-control-label" for="title">
                                    Judul <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="title" id="title"
                                       class="form-control @error('title') is-invalid @enderror"
                                       value="{{ old('title', $info?->title) }}"
                                       placeholder="Judul info / pengumuman">
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Tipe --}}
                            <div class="col-md-4">
                                <label class="form-control-label" for="type">
                                    Tipe <span class="text-danger">*</span>
                                </label>
                                <select name="type" id="type"
                                        class="form-control @error('type') is-invalid @enderror">
                                    <option value="" disabled {{ !old('type', $info?->type) ? 'selected' : '' }}>-- Pilih Tipe --</option>
                                    <option value="announcement" {{ old('type', $info?->type) === 'announcement'     ? 'selected' : '' }}>Pengumuman</option>
                                    <option value="faq"          {{ old('type', $info?->type) === 'faq'              ? 'selected' : '' }}>FAQ</option>
                                    <option value="problem-solution" {{ old('type', $info?->type) === 'problem-solution' ? 'selected' : '' }}>Kendala/Solusi</option>
                                    <option value="other"        {{ old('type', $info?->type) === 'other'            ? 'selected' : '' }}>Lainnya</option>
                                </select>
                                @error('type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Subjudul --}}
                            <div class="col-md-8">
                                <label class="form-control-label" for="subtitle">Subjudul</label>
                                <input type="text" name="subtitle" id="subtitle"
                                       class="form-control @error('subtitle') is-invalid @enderror"
                                       value="{{ old('subtitle', $info?->subtitle) }}"
                                       placeholder="Opsional — deskripsi singkat">
                                @error('subtitle')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Tags --}}
                            <div class="col-md-4">
                                <label class="form-control-label" for="tags">Tags</label>
                                <input type="text" name="tags" id="tags"
                                       class="form-control @error('tags') is-invalid @enderror"
                                       value="{{ old('tags', $info?->tags) }}"
                                       placeholder="tag1, tag2, tag3">
                                <small class="text-muted">Pisahkan dengan koma</small>
                                @error('tags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- ─── Section 2: Konten ──────────────────────────────────── --}}
                    <div class="form-section">
                        <p class="form-section-title"><i class="fas fa-file-alt me-1"></i> Konten</p>

                        <label class="form-control-label" for="content">
                            Isi Konten <span class="text-danger">*</span>
                        </label>
                        <textarea name="content" id="content" rows="10"
                                  class="form-control @error('content') is-invalid @enderror"
                                  placeholder="Tulis konten di sini...">{{ old('content', $info?->content) }}</textarea>
                        @error('content')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    {{-- ─── Section 3: Publikasi ───────────────────────────────── --}}
                    <div class="form-section">
                        <p class="form-section-title"><i class="fas fa-globe me-1"></i> Publikasi</p>

                        <div class="row g-3 align-items-end">
                            {{-- Toggle publikasi --}}
                            <div class="col-md-4">
                                <label class="form-control-label d-block mb-2">Status Publikasi</label>
                                <div class="publish-toggle-wrap">
                                    <input type="hidden" name="is_published" value="0">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="is_published"
                                               name="is_published" value="1"
                                               onchange="syncPublishLabel()"
                                               {{ old('is_published', $info?->is_published ?? true) ? 'checked' : '' }}>
                                    </div>
                                    <span id="publish_badge" class="status-badge-preview"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ─── Submit ─────────────────────────────────────────────── --}}
                    <div class="d-flex gap-2 mt-3">
                        <button class="btn btn-primary px-4" type="submit">
                            <i class="fas fa-save me-1"></i> Simpan
                        </button>
                        <a href="/info" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
        @include('layouts.footers.auth.footer')
    </div>

    @push('js')
        <script src="/vendor/jquery/jquery-3.7.1.min.js"></script>
        <script src="/vendor/select2/select2.min.js"></script>
        <script>
            function syncPublishLabel() {
                const checked = document.getElementById('is_published').checked;
                const badge   = document.getElementById('publish_badge');
                badge.textContent  = checked ? 'Dipublikasi' : 'Tidak Dipublikasi';
                badge.className    = 'status-badge-preview ' + (checked
                    ? 'badge bg-success text-white'
                    : 'badge bg-secondary text-white');
            }

            document.addEventListener('DOMContentLoaded', () => {
                syncPublishLabel();
                [{ selector: '#type', placeholder: 'Pilih Tipe' }].forEach(c => {
                    $(c.selector).select2({ placeholder: c.placeholder, allowClear: true });
                });
            });
        </script>
    @endpush
@endsection
