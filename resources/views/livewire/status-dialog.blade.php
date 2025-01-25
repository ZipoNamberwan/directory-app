<div class="modal-content">
    <div class="modal-header">
        <h5 wire:target="showDialog" wire:loading.remove class="modal-title" id="statusDialogLabel">{{ $type != null ? ($type == 'export' ? 'Status Generate Template' : 'Status Upload Template') : ''}}</h5>
        <button wire:click="showDialog('{{$type}}')" class="m-0 btn btn-icon btn-2 btn-success btn-sm py-1 px-2" type="button">
            <span class="btn-inner--icon"><i class="fas fa-refresh"></i></span>
        </button>
    </div>
    <div class="modal-body">
        <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
                <thead>
                    <tr>
                        @if($type == 'export')
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                            File</th>
                        @endif
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                            Status</th>
                        @if($type == 'import')
                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">
                            Pesan</th>
                        @endif
                        <th
                            class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">
                            Diupload pada</th>
                    </tr>
                </thead>
                <tbody>
                    @if($data)
                    @foreach($data as $item)
                    <tr>
                        @if($type == 'export')
                        <td class="align-center">
                            @if($item->status == 'success')
                            <button wire:click="downloadExport('{{$item->id}}')"
                                class="m-0 btn btn-icon btn-2 btn-outline-secondary btn-sm py-1 px-2" type="button">
                                <span class="btn-inner--icon"><i class="fas fa-download"></i></span>
                            </button>
                            @elseif($item->status == 'loading')
                            <p class="text-xs text-secondary mb-0">File sedang dibuat</p>
                            @else
                            <p class="text-xs text-secondary mb-0">Gagal</p>
                            @endif
                        </td>
                        @endif
                        <td>
                            <p class="text-xs text-secondary mb-0">{{$item->status}}</p>
                        </td>
                        @if($type == 'import')
                        <td>
                            <p>
                                <span class="text-xs text-secondary mb-0">{!!$item->message!!}</span>
                            </p>
                        </td>
                        @endif
                        <td class="align-middle text-center text-sm">
                            <p class="text-xs text-secondary mb-0">{{$item->created_at}}</p>
                        </td>
                    </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn bg-gradient-secondary" data-bs-dismiss="modal">Tutup</button>
    </div>
</div>