<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EnumerationBusinessJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;
    public int $timeout = 120;

    /**
     * @param array $header  CSV header row
     * @param array $rows    Array of row arrays, each matching $header order
     */
    public function __construct(
        public array $header,
        public array $rows,
    ) {}

    public function handle(): void
    {
        $now = now();

        // Turn raw rows into associative arrays keyed by CSV header
        $records = collect($this->rows)
            ->filter(fn($row) => count($row) === count($this->header))
            ->map(fn($row) => array_combine($this->header, $row));

        // Rule 1: skip rows with missing lat/long
        $records = $records->filter(function ($r) {
            return isset($r['geotag_latitude'], $r['geotag_longitude'])
                && $r['geotag_latitude'] !== ''
                && $r['geotag_longitude'] !== '';
        });

        if ($records->isEmpty()) {
            return;
        }

        $skippedNoGeo = 0;
        $rows = [];

        foreach ($records as $r) {
            if (!is_numeric($r['geotag_latitude']) || !is_numeric($r['geotag_longitude'])) {
                $skippedNoGeo++;
                continue;
            }

            $lat = (float) $r['geotag_latitude'];
            $lng = (float) $r['geotag_longitude'];

            $name = ($r['nama_kk'] ?? '') !== '' ? $r['nama_kk'] : ($r['nama_usaha_bang'] ?? null);

            $rows[] = [
                'id'                 => (string) Str::uuid(),
                'name'               => $name,
                'assignment_id'      => $r['assignment_id'],
                'building_number'    => $r['no_bang'] ?? null,
                'latitude'           => $lat,
                'longitude'          => $lng,
                'original_area'      => $r['level_6_full_code'],
                'original_latitude'  => $lat,
                'original_longitude' => $lng,
                'regency_id'         => null,
                'subdistrict_id'     => null,
                'village_id'         => null,
                'sls_id'             => null,
                'coordinate'         => DB::raw("ST_SRID(POINT({$lng}, {$lat}), 4326)"),
                'created_at'         => $now,
                'updated_at'         => $now,
            ];
        }

        if (!empty($rows)) {
            // Existing assignment_id -> update name/building_number only.
            // New assignment_id -> insert the full row (requires the unique
            // index on assignment_id added in the 2026_09_18 migration).
            DB::table('enumeration_business')->upsert(
                $rows,
                ['assignment_id'],
                ['name', 'building_number', 'original_latitude', 'original_longitude', 'updated_at']
            );
        }

        Log::info("CSV chunk import: upserted=" . count($rows) . ", skipped_no_geo={$skippedNoGeo}");
    }

    public function failed(Throwable $e): void
    {
        Log::error('EnumerationBusinessJob failed: ' . $e->getMessage());
    }
}
