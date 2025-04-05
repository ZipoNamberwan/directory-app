<?php

namespace App\Jobs;

use App\Models\MarketBusiness;
use App\Models\MarketUploadStatus;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class MarketUploadNotificationJob implements ShouldQueue
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
        $status = MarketUploadStatus::find($this->uuid);
        if ($status->message != null) {
            $status->update([
                'status' => 'success with error',
            ]);
        } else {
            $status->update([
                'status' => 'success',
            ]);
        }

        MarketBusiness::where([
            'user_id' => $status->user_id,
            'market_id' => $status->market_id
        ])->where('upload_id', '!=', $status->id)->forceDelete();
    }
}
