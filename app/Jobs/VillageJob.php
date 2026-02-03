<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\AreaPeriod;
use App\Models\Subdistrict;
use App\Models\Village;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class VillageJob implements ShouldQueue
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
                'short_code' => $record['des'],
                'long_code' => $record['prov'] . $record['kab'] . $record['kec'] . $record['des'],
                'name' => $record['des_name'],
                'subdistrict_id' => Subdistrict::withoutGlobalScopes()->where('area_period_id', $periodId)->where('long_code', $record['prov'] . $record['kab'] . $record['kec'])->first()->id,
                'area_period_id' => $periodId,
            ];
        }

        foreach (DatabaseSelector::getListConnections() as $connection) {
            Village::on($connection)->insert($data);
        }
    }
}
