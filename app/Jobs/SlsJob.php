<?php

namespace App\Jobs;

use App\Models\Sls;
use App\Models\Village;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

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
        foreach ($this->records as $record) {
            $village = Village::where(['long_code' => $record['prov'].$record['kab'].$record['kec'].$record['des']])->first();
            Sls::create(['short_code' => $record['sls'], 'long_code' => $record['prov'].$record['kab'].$record['kec'].$record['des'].$record['sls'], 'name' => $record['sls_name'], 'village_id' => $village->id,]);
        }
    }
}
