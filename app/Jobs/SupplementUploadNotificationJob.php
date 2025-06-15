<?php

namespace App\Jobs;

use App\Models\SupplementBusiness;
use App\Models\SupplementUploadStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SupplementUploadNotificationJob implements ShouldQueue
{
    use Queueable;

    protected $uuid;
    /**
     * Create a new job instance.
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $status = SupplementUploadStatus::find($this->uuid);
        if ($status->status != 'failed') {
            if ($status->processed_count == 0) {
                $status->update([
                    'status' => 'failed',
                    'message' => 'File kosong atau tidak memiliki baris yang dapat diproses.',
                ]);
            } else {
                if ($status->message != null) {
                    $status->update([
                        'status' => 'success with error',
                    ]);
                } else {
                    $status->update([
                        'status' => 'success',
                    ]);
                }

                SupplementBusiness::where(['user_id' => $status->user_id,])
                    ->where('upload_id', '!=', $status->id)
                    ->whereHas('project', function ($query) {
                        $query->where('type', 'swmaps supplement');
                    })->forceDelete();
            }
        }
    }
}
