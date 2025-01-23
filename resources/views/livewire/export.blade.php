<div>
    <button wire:click="export" class="btn btn-info">
        <i class="fas fa-play me-2"></i>
        Buat
    </button>

    @if($exporting && !$exportFinished)
    <div class="d-inline" wire:poll="updateExportProgress">Exporting...please wait.</div>
    @endif

    @if($exportFinished)
    Done. Download file <a class="stretched-link" wire:click="downloadExport">here</a>
    @endif
</div>