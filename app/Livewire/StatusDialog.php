<?php

namespace App\Livewire;

use App\Models\AssignmentStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\Attributes\On;

class StatusDialog extends Component
{
    public $type;
    public $data;

    #[On('show-dialog')]
    public function showDialog($type)
    {
        $this->type = $type;
        $this->data = AssignmentStatus::where('type', $type)
            ->where('user_id', Auth::id())->get()->sortByDesc('created_at')->skip(0)->take(10);
    }

    public function downloadExport($file)
    {
        if ($this->type == 'export') {
            return Storage::download($file . '.xlsx');
        } else if ($this->type == 'download-sls-business' || $this->type == 'download-non-sls-business') {
            return Storage::download($file . '.csv');
        }
    }

    public function render()
    {
        return view('livewire.status-dialog');
    }
}
