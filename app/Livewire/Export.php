<?php

namespace App\Livewire;

use App\Exports\SlsAssignmentExport;
use Livewire\Component;
use App\Jobs\AssignmentNotificationExportJob;
use App\Models\ExportAssignmentStatus;
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
        $status = ExportAssignmentStatus::where('user_id', Auth::id())
            ->where(function ($query) {
                $query->where('status', 'start')
                    ->orWhere('status', 'loading');
            })->first();

        if ($status == null) {
            $this->exporting = true;
            $this->exportFinished = false;

            $uuid = (string) Str::uuid();
            $this->uuid = $uuid;

            ExportAssignmentStatus::create([
                'uuid' => $uuid,
                'status' => 'start',
                'user_id' => Auth::id(),
            ]);

            (new SlsAssignmentExport(User::find(Auth::id())->regency_id, $uuid))->store($uuid . '.xlsx')->chain([
                new AssignmentNotificationExportJob($uuid),
            ]);
        }
    }

    public function downloadExport()
    {
        return Storage::download($this->uuid . '.xlsx');
    }

    public function updateExportProgress()
    {
        $this->exportFinished = ExportAssignmentStatus::where('uuid', $this->uuid)->first()->status == 'success';

        if ($this->exportFinished) {
            $this->exporting = false;
        }
    }

    public function render()
    {
        return view('livewire.export');
    }
}
