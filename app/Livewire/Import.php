<?php

namespace App\Livewire;

use App\Imports\SlsAssignmentImport;
use App\Jobs\AssignmentNotificationJob;
use App\Models\AssignmentStatus;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Illuminate\Support\Str;

class Import extends Component
{
    use WithFileUploads;

    public $importFile;
    public $importing = false;
    public $importFilePath;
    public $importFinished = false;
    public $uuid = null;
    public $status = null;

    public function import()
    {
        $this->validate([
            'importFile' => 'required|mimes:xlsx,csv|max:2048',
        ]);

        $status = AssignmentStatus::where('user_id', Auth::id())
            ->where('type', 'import')
            ->where('status', 'loading')->first();

        if ($status == null) {
            $this->importing = true;
            $this->importFinished = false;
            $this->importFilePath = $this->importFile->store('imports');

            $uuid = (string) Str::uuid();
            $this->uuid = $uuid;
            AssignmentStatus::create([
                'id' => $uuid,
                'user_id' => Auth::id(),
                'status' => 'start',
                'type' => 'import',
            ]);

            try {
                (new SlsAssignmentImport(User::find(Auth::id())->regency_id, $uuid))->queue($this->importFilePath)->chain([
                    new AssignmentNotificationJob($uuid),
                ]);
            } catch (Exception $e) {
                AssignmentStatus::find($uuid)->update([
                    'status' => 'failed',
                ]);
            }
        }
    }

    public function updateImportProgress()
    {
        $status = AssignmentStatus::find($this->uuid);
        if ($status) {
            $this->importFinished = $status->status == 'success' || $status->status == 'success with error';
        }

        if ($this->importFinished) {
            $this->importing = false;
            $this->status = $status->status;
        }
    }

    public function showDialog()
    {
        $this->dispatch('show-dialog', 'import');
    }

    public function render()
    {
        return view('livewire.import');
    }
}
