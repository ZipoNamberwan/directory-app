<?php

namespace App\Jobs;

use App\Models\Market;
use App\Models\Regency;
use App\Models\Subdistrict;
use App\Models\Village;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class MarketJob implements ShouldQueue
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
            $regency = Regency::find(substr($record['ID Desa'], 0, 4));
            $subdistrict = Subdistrict::find(substr($record['ID Desa'], 0, 7));
            $village = Village::find(substr($record['ID Desa'], 0, 10));

            if ($regency != null && $subdistrict != null && $village != null) {
                $uuid = Str::uuid();
                Market::create([
                    'id' => $uuid,
                    'name' => $record['Nama Pasar'],
                    'regency_id' => $regency->id,
                    'subdistrict_id' => $subdistrict->id,
                    'village_id' => $village->id,
                ]);
            }
        }
    }
}
