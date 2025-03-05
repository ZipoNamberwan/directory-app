<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
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
        foreach (DatabaseSelector::getListConnections() as $connection) {
            $data = [];
            foreach ($this->records as $record) {
                $data[] = [
                    'id' => $record['prov'] . $record['kab'] . $record['kec'],
                    'short_code' => $record['kec'],
                    'long_code' => $record['prov'] . $record['kab'] . $record['kec'],
                    'name' => $record['kec_name'],
                    'regency_id' => Regency::find($record['prov'] . $record['kab'])->id,
                ];
            }
            Subdistrict::on($connection)->insert($data);
        }
    }
}
