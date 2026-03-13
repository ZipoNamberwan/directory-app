<?php

namespace App\Jobs;

use App\Models\SbrBusiness;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SbrJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */

    /**
     * Create a new job instance.
     */
    public function __construct(public string $filePath) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (($handle = fopen($this->filePath, 'r')) !== false) {

            $header = null;
            $batch = [];

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {

                if (!$header) {
                    $header = $row;
                    continue;
                }

                $record = array_combine($header, $row);

                $batch[] = $record;

                // insert every 1000 rows
                if (count($batch) === 1000) {
                    $this->insertBatch($batch);
                    $batch = [];
                }
            }

            if (!empty($batch)) {
                $this->insertBatch($batch);
            }

            fclose($handle);
        }
    }

    public function insertBatch($records)
    {
        $data = [];
        foreach ($records as $record) {
            if ($record['extracted_coordinate_validation'] === 'invalid') {
                continue; // Skip records with invalid coordinates
            }
            $uuid = Str::uuid()->toString();
            $data[] = [
                'id' => $uuid,
                'name' => $record['nama_usaha'],
                'status' => 'Tetap',
                'address' => $record['alamat_usaha'],
                'sector' => $record['extracted_kategori'],
                'description' => '-',
                'latitude' => $record['valid_latitude'],
                'longitude' => $record['valid_longitude'],
                'idsbr' => $record['idsbr'],
                'status_sbr' => $record['gcs_result'],
                // 👇 spatial column
                'coordinate' => DB::raw(
                    "ST_SRID(POINT({$record['valid_longitude']}, {$record['valid_latitude']}), 4326)"
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        collect($data)
            ->chunk(1000)
            ->each(fn($chunk) => SbrBusiness::insert($chunk->toArray()));
    }
}
