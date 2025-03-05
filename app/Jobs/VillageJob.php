<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\Subdistrict;
use App\Models\Village;
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
        foreach (DatabaseSelector::getListConnections() as $connection) {
            $data = [];
            foreach ($this->records as $record) {
                $data[] = [
                    'id' => $record['prov'] . $record['kab'] . $record['kec'] . $record['des'],
                    'short_code' => $record['des'],
                    'long_code' => $record['prov'] . $record['kab'] . $record['kec'] . $record['des'],
                    'name' => $record['des_name'],
                    'subdistrict_id' => Subdistrict::find($record['prov'] . $record['kab'] . $record['kec'])->id,
                ];
            }
            Village::on($connection)->insert($data);
        }
    }
}
