<div>
    <div class="d-flex align-items-center gap-2">
        <button wire:click="export" class="btn btn-info">
            <i class="fas fa-play me-2"></i>
            Buat
        </button>
        <button wire:click="showDialog" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#statusDialog">
            <i class="fas fa-circle-info me-2"></i>
            Status
        </button>
    </div>

    @if($exporting && !$exportFinished)
    <div class="d-inline" wire:poll="updateExportProgress">
        <p class="small mb-3">
            Exporting...mohon tunggu beberapa saat
        </p>
    </div>
    @endif

    @if($exportFinished)
    <div>
        <p class="small">
            Selesai. Unduh file <a style="cursor: pointer" wire:click="downloadExport"><strong class="text-info">di sini</strong></a>
        </p>
    </div>
    @endif

</div>