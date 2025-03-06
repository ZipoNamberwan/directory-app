<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Models\SlsBusiness;
use App\Models\User;

class DummyAssignmentJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pcl = User::where('email', 'pcl01@gmail.com')->first();
        $ids = SlsBusiness::on(DatabaseSelector::getConnection($pcl->regency_id))->where('regency_id', $pcl->regency_id)->skip(0)->take(1000)->pluck('id');
        SlsBusiness::on(DatabaseSelector::getConnection($pcl->regency_id))->whereIn('id', $ids)->update(['pcl_id' => $pcl->id]);
    }
}
