<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\FailedBusiness;
use App\Models\Sls;
use App\Models\SlsBusiness;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EformJob implements ShouldQueue
{
    use Queueable;

    public $records;
    public $tries = 3;
    public $backoff = 10;

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
            if ($record['idsubsls'] == null) {
                continue;
            }

            $sls = Sls::find($record['idsubsls']);
            $regencyId = substr($record['idsubsls'], 0, 4);

            if ($sls) {
                $existingBusiness = SlsBusiness::on(DatabaseSelector::getConnection($regencyId))
                    ->where('note', $record['ID ASSIGNMENT'])
                    ->where('sls_id', $record['idsubsls'])
                    ->first();

                if ($existingBusiness) {
                    continue; // Skip to the next record if duplicate found
                }

                $data = [
                    'id' => (string) Str::uuid(),
                    'name' => $record['NAMA USAHA'],
                    'initial_name' => $record['NAMA USAHA'],
                    'sls_id' => $record['idsubsls'],
                    'village_id' => substr($record['idsubsls'], 0, 10),
                    'subdistrict_id' => substr($record['idsubsls'], 0, 7),
                    'regency_id' => $regencyId,
                    'status_id' => 1,
                    'owner' => $record['KEPALA KELUARGA'],
                    'initial_owner' => $record['KEPALA KELUARGA'],
                    'source' => 'EFORM',
                    'initial_address' => $record['ALAMAT'],
                    'note' => $record['ID ASSIGNMENT'],
                ];
                SlsBusiness::on(DatabaseSelector::getConnection($regencyId))->insert($data);
            } else {
                FailedBusiness::create(['record' => json_encode($record)]);
            }
        }
    }
}
