<?php

namespace App\Livewire;

use App\Jobs\BusinessExportJob;
use App\Models\AssignmentStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Illuminate\Support\Str;

class Download extends Component
{
    public $exporting = false;
    public $exportFinished = false;
    public $uuid = null;
    public $type;
    public $color;

    public function download()
    {

        $userId = Auth::id();
        $typeKey = $this->type === 'sls' ? 'download-sls-business' : 'download-non-sls-business';

        $status = AssignmentStatus::where('user_id', $userId)
            ->where('type', $typeKey)
            ->whereIn('status', ['start', 'loading'])
            ->first();

        if ($status) {
            return;
        }

        $this->exporting = true;
        $this->exportFinished = false;

        $uuid = (string) Str::uuid();
        $this->uuid = $uuid;

        AssignmentStatus::create([
            'id' => $uuid,
            'status' => 'start',
            'user_id' => $userId,
            'type' => $typeKey,
        ]);

        $regencyId = User::find($userId)->regency_id;

        BusinessExportJob::dispatch($regencyId, $uuid, $typeKey);
    }

    public function downloadExport()
    {
        return Storage::download($this->uuid . '.csv');
    }

    public function updateExportProgress()
    {
        $status = AssignmentStatus::find($this->uuid);
        if ($status) {
            $this->exportFinished = $status->status == 'success';
        }

        if ($this->exportFinished) {
            $this->exporting = false;
        }
    }

    public function showDialog()
    {
        $this->dispatch('show-dialog', $this->type === 'sls' ? 'download-sls-business' : 'download-non-sls-business');
    }

    public function render()
    {
        return view('livewire.download');
    }
}
