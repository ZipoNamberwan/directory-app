<?php

namespace App\Livewire;

use App\Exports\SlsAssignmentExport;
use Livewire\Component;
use App\Jobs\AssignmentNotificationJob;
use App\Models\AssignmentStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Export extends Component
{

    public $exporting = false;
    public $exportFinished = false;
    public $uuid = null;

    public function export()
    {
        $this->exporting = true;
        $this->exportFinished = false;

        $uuid = (string) Str::uuid();
        $this->uuid = $uuid;

        AssignmentStatus::create([
            'uuid' => $uuid,
            'status' => 'start',
            'type' => 'export',
        ]);

        (new SlsAssignmentExport(User::find(Auth::id())->regency_id))->store($uuid . '.xlsx')->chain([
            new AssignmentNotificationJob($uuid, 'export'),
        ]);
    }

    public function downloadExport()
    {
        return Storage::download($this->uuid . '.xlsx');
    }

    public function updateExportProgress()
    {
        $this->exportFinished = AssignmentStatus::where('uuid', $this->uuid)->first()->status == 'success';

        if ($this->exportFinished) {
            $this->exporting = false;
        }
    }

    public function render()
    {
        return view('livewire.export');
    }
}
