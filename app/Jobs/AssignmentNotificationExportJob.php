<?php

namespace App\Jobs;

use App\Models\ExportAssignmentStatus;
use App\Models\Status;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssignmentNotificationExportJob implements ShouldQueue
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
        ExportAssignmentStatus::where('uuid', $this->uuid)->update([
            'status' => 'success',
        ]);
    }
}
