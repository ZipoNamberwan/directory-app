<?php

namespace App\Jobs;

use App\Models\AgricultureBusiness;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgricultureJob implements ShouldQueue
{
    use Queueable;

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

            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                if (!$header) {
                    $header = $row;
                    continue;
                }

                $record = array_combine($header, $row);

                $batch[] = $record;

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

        $subsectorMap = [
            1 => 'Tanaman pangan',
            2 => 'Hortikultura',
            3 => 'Perkebunan',
            4 => 'Peternakan',
            5 => 'Kehutanan',
            6 => 'Budidaya/Penangkapan Ikan',
            7 => 'Jasa Pertanian',
        ];

        foreach ($records as $record) {
            preg_match_all('/\d/', (string) $record['data_subsektor'], $matches);

            if (empty($matches[0])) {
                $description = null;
            } else {
                $descriptions = array_map(
                    fn($digit) => $subsectorMap[(int)$digit] ?? $digit,
                    $matches[0]
                );
                $description = implode(', ', $descriptions);
            }

            $uuid = Str::uuid()->toString();

            $data[] = [
                'id' => $uuid,
                'name' => $record['data_nama_krt'],
                'sector' => 'A',
                'description' => $description,
                'owner' => $record['data_nama_krt'],
                'latitude' => $record['latitude'],
                'longitude' => $record['longitude'],
                'id_agriculture' => $record['uuid'],
                'coordinate' => DB::raw(
                    "ST_SRID(POINT({$record['longitude']}, {$record['latitude']}), 4326)"
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        collect($data)
            ->chunk(1000)
            ->each(fn($chunk) => AgricultureBusiness::insert($chunk->toArray()));
    }
}
