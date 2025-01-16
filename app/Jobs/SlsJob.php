<?php

namespace App\Jobs;

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
        $data = [];
        foreach ($this->records as $record) {
            $data[] = [
                'id' => $record['prov'] . $record['kab'] . $record['kec'] . $record['des'] . $record['sls'] . $record['subsls'],
                'short_code' => $record['sls'] . $record['subsls'],
                'long_code' => $record['prov'] . $record['kab'] . $record['kec'] . $record['des'] . $record['sls'] . $record['subsls'],
                'name' => $record['sls_name'],
                'village_id' => Village::find($record['prov'] . $record['kab'] . $record['kec'] . $record['des'])->id,
            ];
        }

        Sls::insert($data);
    }
}
