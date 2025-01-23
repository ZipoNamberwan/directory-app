<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use App\Models\Status;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssignmentNotificationJob implements ShouldQueue
{
    use Queueable;
    protected $uuid;
    protected $type;

    /**
     * Create a new job instance.
     */
    public function __construct($uuid, $type)
    {
        $this->uuid = $uuid;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        AssignmentStatus::where('uuid', $this->uuid)->update([
            'status' => 'success',
        ]);
    }
}
