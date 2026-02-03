<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\AreaPeriod;
use App\Models\Regency;
use App\Models\Subdistrict;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SubdistrictJob implements ShouldQueue
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
                'short_code' => $record['kec'],
                'long_code' => $record['prov'] . $record['kab'] . $record['kec'],
                'name' => $record['kec_name'],
                'regency_id' => Regency::withoutGlobalScopes()->where('area_period_id', $periodId)->where('long_code', $record['prov'] . $record['kab'])->first()->id,
                'area_period_id' => $periodId,
            ];
        }

        foreach (DatabaseSelector::getListConnections() as $connection) {
            Subdistrict::on($connection)->insert($data);
        }
    }
}
