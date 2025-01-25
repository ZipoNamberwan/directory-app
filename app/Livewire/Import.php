<?php

namespace App\Livewire;

use App\Imports\SlsAssignmentImport;
use App\Jobs\AssignmentNotificationJob;
use App\Models\AssignmentStatus;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use Illuminate\Support\Str;

class Import extends Component
{
    use WithFileUploads;

    public $batchId;
    public $importFile;
    public $importing = false;
    public $importFilePath;
    public $importFinished = false;

    public function import()
    {
        $this->validate([
            'importFile' => 'required',
        ]);

        $status = AssignmentStatus::where('user_id', Auth::id())
            ->where('type', 'import')
            ->where('status', 'loading')->first();

        if ($status == null) {
            $this->importing = true;
            $this->importFilePath = $this->importFile->store('imports');

            $uuid = (string) Str::uuid();
            AssignmentStatus::create([
                'id' => $uuid,
                'user_id' => Auth::id(),
                'status' => 'start',
                'type' => 'import',
            ]);

            (new SlsAssignmentImport(User::find(Auth::id())->regency_id, $uuid))->queue($this->importFilePath)->chain([
                new AssignmentNotificationJob($uuid),
            ]);
        }
    }

    // public function getImportBatchProperty()
    // {
    //     if (!$this->batchId) {
    //         return null;
    //     }

    //     return Bus::findBatch($this->batchId);
    // }

    // public function updateImportProgress()
    // {
    //     $this->importFinished = $this->importBatch->finished();

    //     if ($this->importFinished) {
    //         Storage::delete($this->importFilePath);
    //         $this->importing = false;
    //     }
    // }

    public function render()
    {
        return view('livewire.import');
    }
}
