<?php

namespace App\Jobs;

use App\Helpers\DatabaseSelector;
use App\Models\FailedBusiness;
use App\Models\NonSlsBusiness;
use App\Models\SlsBusiness;
use App\Models\Sls;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Str;

class BusinessJob implements ShouldQueue
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
        $dataSls = [];
        $dataNonSls = [];

        foreach ($this->records as $record) {
            $sls = Sls::find($record['idsls'] . '00');
            if ($sls) {
                $dataSls[] = [
                    'id' => (string) Str::uuid(),
                    'name' => $record['NamaUsaha'] ?? $record['nmusaha'],
                    'sls_id' => $record['idsls'] . '00',
                    'village_id' => substr($record['idsls'], 0, 10),
                    'subdistrict_id' => substr($record['idsls'], 0, 7),
                    'regency_id' => substr($record['idsls'], 0, 4),
                    'status_id' => 1,
                    'owner' => $record['nmpengusaha'],
                    'source' => $record['sumber'],
                    'initial_address' => $record['Alamat'],
                    'category' => $record['kategori'],
                    'kbli' => $record['kbli'],
                    'lat' => $record['latitude'],
                    'long' => $record['longitude'],
                ];
            } else {
                $level = 'regency';
                $villageId = null;
                $subdistrictId = null;
                $regencyId = null;
                if (strlen($record['iddesa']) == 7) {
                    if (substr($record['iddesa'], -3) == '000' || substr($record['iddesa'], -3) == '999') {
                        $level = 'regency';
                    } else {
                        $level = 'subdistrict';
                        $subdistrictId = substr($record['iddesa'], 0, 7);
                    }
                } else if (strlen($record['iddesa']) == 10) {
                    $subdistrictId = substr($record['iddesa'], 0, 7);
                    if (substr($record['iddesa'], -3) == '000' || substr($record['iddesa'], -3) == '999') {
                        $level = 'subdistrict';
                    } else {
                        $level = 'village';
                        $villageId = substr($record['iddesa'], 0, 10);
                    }
                }

                $regencyId = "35" . substr($record['kab'], 1, 2);
                if ($regencyId == "35") {
                    $regencyId = substr($record['iddesa'], 0, 4);
                }

                $dataNonSls[] = [
                    'id' => (string) Str::uuid(),
                    'name' => $record['NamaUsaha'] ?? $record['nmusaha'],
                    'sls_id' => null,
                    'village_id' => $villageId,
                    'subdistrict_id' => $subdistrictId,
                    'regency_id' => $regencyId,
                    'status_id' => 1,
                    'level' => $level,
                    'owner' => $record['nmpengusaha'],
                    'source' => $record['sumber'],
                    'initial_address' => $record['Alamat'],
                    'category' => $record['kategori'],
                    'kbli' => $record['kbli'],
                    'idsbr' => $record['idsbr'],
                ];
            }
        }
        if (count($dataSls) > 0) {
            SlsBusiness::on(DatabaseSelector::getConnection($dataSls[0]['regency_id']))->insert($dataSls);
        }
        if (count($dataNonSls) > 0) {
            NonSlsBusiness::on(DatabaseSelector::getConnection($dataNonSls[0]['regency_id']))->insert($dataNonSls);
        }

        // foreach ($dataSls as $data) {
        //     try {
        //         SlsBusiness::on(DatabaseSelector::getConnection($data['regency_id']))->create($data);
        //     } catch (Exception $e) {
        //         FailedBusiness::create(['record' => json_encode($data)]);
        //     }
        // }

        // foreach ($dataNonSls as $data) {
        //     try {
        //         NonSlsBusiness::on(DatabaseSelector::getConnection($data['regency_id']))->create($data);
        //     } catch (Exception $e) {
        //         FailedBusiness::create(['record' => json_encode($data)]);
        //     }
        // }
    }
}
