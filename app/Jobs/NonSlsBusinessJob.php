<?php

namespace App\Jobs;

use App\Models\NonSlsBusiness;
use App\Models\Sls;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class NonSlsBusinessJob implements ShouldQueue
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
                $randomSelection = ['regency', 'subdistrict', 'village'][array_rand(['regency', 'subdistrict', 'village'])];

                // Define base IDs
                $subdistrictId = substr($record['idsls'], 0, 7);
                $villageId = substr($record['idsls'], 0, 10);

                // Apply the nullification rule based on selection
                if ($randomSelection === 'regency') {
                    $subdistrictId = null;
                    $villageId = null;
                } elseif ($randomSelection === 'subdistrict') {
                    $villageId = null;
                }

                // Build the data array
                $data[] = [
                    'id' => (string) Str::uuid(),
                    'name' => $record['Nama Usaha'] ?? $record['nmusaha'],
                    'sls_id' => null,
                    'village_id' => $villageId,
                    'subdistrict_id' => $subdistrictId,
                    'regency_id' => substr($record['idsls'], 0, 4),
                    'status_id' => 1,
                ];
            }
        }
        NonSlsBusiness::insert($data);
    }
}
