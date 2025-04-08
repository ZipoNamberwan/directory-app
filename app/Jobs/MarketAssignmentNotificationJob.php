<?php

namespace App\Jobs;

use App\Models\MarketAssignmentStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MarketAssignmentNotificationJob implements ShouldQueue
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
        $status = MarketAssignmentStatus::find($this->uuid);
        if ($status->message != null) {
            $status->update([
                'status' => 'success with error',
            ]);
        } else {
            $status->update([
                'status' => 'success',
            ]);
        }
    }
}
