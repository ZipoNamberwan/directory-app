<?php

namespace App\Jobs;

use App\Models\AgricultureBusiness;
use App\Models\FailedBusiness;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AgricultureJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $filePath) {}

    public function handle(): void
    {
        if (($handle = fopen($this->filePath, 'r')) !== false) {
            $header = null;
            $batch = [];
            $rowNumber = 1; // header is row 1

            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                if (!$header) {
                    $header = $row;
                    continue;
                }

                $rowNumber++;

                // Combine headers with row values safely
                if (count($header) !== count($row)) {
                    // Skip or log corrupted row lengths if necessary
                    continue;
                }

                $record = array_combine($header, $row);

                $batch[] = [
                    'row' => $rowNumber,
                    'record' => $record,
                ];

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

    protected function isValidCoordinate($value): bool
    {
        return $value !== null && $value !== '' && is_numeric($value);
    }

    public function insertBatch($rows)
    {
        $data = [];
        $failed = [];

        $subsectorMap = [
            1 => 'Tanaman pangan',
            2 => 'Hortikultura',
            3 => 'Perkebunan',
            4 => 'Peternakan',
            5 => 'Kehutanan',
            6 => 'Budidaya/Penangkapan Ikan',
            7 => 'Jasa Pertanian',
        ];

        foreach ($rows as $item) {
            $record = $item['record'];
            $rowNumber = $item['row'];

            // Updated column keys mapping directly to your split_1.csv structure
            $latitude = $record['latitude'] ?? null;
            $longitude = $record['longitude'] ?? null;
            $name = $record['nama_krt'] ?? $record['nama'] ?? null; 
            $subsectorRaw = $record['subsektor'] ?? null;

            // Fallback strategy if coordinates are displaced inside other columns
            if (!$this->isValidCoordinate($latitude) || !$this->isValidCoordinate($longitude)) {
                $latitude = $record['id_project_kategori'] ?? null;
                $longitude = $record['accuracy'] ?? null;
                $name = $record['source_file'] ?? $name;
                $subsectorRaw = $record['idsls'] ?? $subsectorRaw;
            }

            $reasons = [];

            // Validation: Coordinates
            if (!$this->isValidCoordinate($latitude) || !$this->isValidCoordinate($longitude)) {
                $reasons[] = 'missing_or_invalid_coordinates';
            }

            // Validation: Name
            if ($name === null || trim((string) $name) === '') {
                $reasons[] = 'missing_name';
            }

            // Parse subsector formatting (e.g., "[1 3 4 5]")
            $description = null;
            $hasInvalidSubsector = false;

            preg_match_all('/\d/', (string) $subsectorRaw, $matches);

            if (empty($matches[0])) {
                $hasInvalidSubsector = true; 
            } else {
                $descriptions = [];
                foreach ($matches[0] as $digit) {
                    $digit = (int) $digit;
                    if (isset($subsectorMap[$digit])) {
                        $descriptions[] = $subsectorMap[$digit];
                    } else {
                        $hasInvalidSubsector = true; 
                    }
                }
                $description = !empty($descriptions) ? implode(', ', $descriptions) : null;
            }

            if ($hasInvalidSubsector || $description === null) {
                $reasons[] = 'missing_or_invalid_subsector';
            }

            if (!empty($reasons)) {
                $failed[] = [
                    'record' => json_encode([
                        'file' => basename($this->filePath),
                        'row' => $rowNumber,
                        'reasons' => $reasons,
                        'data' => $record,
                    ], JSON_UNESCAPED_UNICODE),
                ];
                continue;
            }

            $uuid = Str::uuid()->toString();

            $data[] = [
                'id' => $uuid,
                'name' => $name,
                'sector' => 'A',
                'description' => $description,
                'owner' => $name,
                'latitude' => $latitude,
                'longitude' => $longitude,
                // Maps to the unique ID present in your CSV file
                'id_agriculture' => $record['id'] ?? null, 
                'coordinate' => DB::raw(
                    "ST_SRID(POINT({$longitude}, {$latitude}), 4326)"
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($data)) {
            collect($data)
                ->chunk(1000)
                ->each(fn($chunk) => AgricultureBusiness::insert($chunk->toArray()));
        }

        if (!empty($failed)) {
            collect($failed)
                ->chunk(1000)
                ->each(fn($chunk) => FailedBusiness::insert($chunk->toArray()));
        }
    }
}