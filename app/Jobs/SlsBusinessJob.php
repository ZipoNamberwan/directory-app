<?php

namespace App\Jobs;

use App\Models\SlsBusiness;
use App\Models\Sls;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class SlsBusinessJob implements ShouldQueue
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
            $sls = Sls::find($record['idsls'] . '00');
            if ($sls) {
                $data[] = [
                    'id' => (string) Str::uuid(),
                    'name' => $record['Nama Usaha'] ?? $record['nmusaha'],
                    'sls_id' => $record['idsls'] . '00',
                    'village_id' => substr($record['idsls'], 0, 10),
                    'subdistrict_id' => substr($record['idsls'], 0, 7),
                    'regency_id' => substr($record['idsls'], 0, 4),
                    'status_id' => 1,
                ];
            }
        }
        SlsBusiness::insert($data);
    }
}
