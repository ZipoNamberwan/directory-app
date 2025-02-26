<div>
    <form wire:submit.prevent="import" enctype="multipart/form-data">
        @csrf
        <div class="d-flex align-items-center flex-wrap gap-2">
            <div>
                <input type="file" wire:model="importFile" class="form-control" accept=".xlsx,.csv">
            </div>
            <div>
                <button type="submit" class="btn btn-success my-0">
                    <i class="fas fa-upload me-2"></i>
                    Import
                </button>
            </div>
            <div>
                <button type="button" wire:click="showDialog" class="btn btn-outline-success my-0"
                    data-bs-toggle="modal" data-bs-target="#statusDialog">
                    <i class="fas fa-circle-info me-2"></i>
                    Status
                </button>
            </div>
        </div>
    </form>


    @if ($importing && !$importFinished)
        <div wire:poll="updateImportProgress" class="mt-3">
            <div>
                <p class="small">
                    <strong>Sedang mengimport</strong>... Tab bisa ditutup karena proses import berjalan di
                    <strong>background</strong> <br>
                    Gunakan tombol status untuk melihat <strong>status import</strong>
                </p>
            </div>
        </div>
    @endif

    @if ($importFinished)
        @if ($status == 'success')
            <div class="mt-3">
                <p class="small">
                    Sukses melakukan import. Silakan <a href="/tambah-direktori">refresh</a> halaman ini.
                </p>
            </div>
        @elseif($status == 'success with error')
            <div class="mt-3">
                <p class="small">
                    Sukses melakukan import, namun beberapa baris gagal. Gunakan tombol status untuk melihat detail.
                </p>
            </div>
        @endif
    @endif
