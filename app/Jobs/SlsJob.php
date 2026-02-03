<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\AreaPeriod;
use App\Models\Sls;
use App\Models\Village;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SlsJob implements ShouldQueue
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
        $version = 2;
        $periodId = AreaPeriod::where('period_version', $version)->first()->id;

        $data = [];

        foreach ($this->records as $record) {
            $uuid = Str::uuid()->toString();
            $data[] = [
                'id' => $uuid,
                'short_code' => $record['sls'] . $record['subsls'],
                'long_code' => $record['prov'] . $record['kab'] . $record['kec'] . $record['des'] . $record['sls'] . $record['subsls'],
                'name' => $record['sls_name'],
                'village_id' => Village::withoutGlobalScopes()->where('area_period_id', $periodId)->where('long_code', $record['prov'] . $record['kab'] . $record['kec'] . $record['des'])->first()->id,
                'area_period_id' => $periodId,
            ];
        }
        foreach (DatabaseSelector::getListConnections() as $connection) {
            Sls::on($connection)->insert($data);
        }
    }
}
