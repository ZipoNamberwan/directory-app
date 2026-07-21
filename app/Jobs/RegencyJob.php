<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\AreaPeriod;
use App\Models\Regency;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class RegencyJob implements ShouldQueue
{
    use Queueable;

    public $records;

    /**
     * Create a new job instance.
     */
    public function __construct($records)
    {
        $this->records = $records;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $version = 3;
        $periodId = AreaPeriod::where('period_version', $version)->first()->id;

        $data = [];
        foreach ($this->records as $record) {
            $uuid = Str::uuid()->toString();
            $data[] = [
                'id' => $uuid,
                'short_code' => $record['kab'],
                'long_code' => $record['prov'] . $record['kab'],
                'name' => $record['kab_name'],
                'area_period_id' => $periodId,
            ];
        }

        foreach (DatabaseSelector::getListConnections() as $connection) {
            Regency::on($connection)->insert($data);
        }
    }
}
