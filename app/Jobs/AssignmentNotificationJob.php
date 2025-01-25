<?php

namespace App\Jobs;

use App\Models\AssignmentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AssignmentNotificationJob implements ShouldQueue
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
        AssignmentStatus::find($this->uuid)->update([
            'status' => 'success',
        ]);
    }
}
