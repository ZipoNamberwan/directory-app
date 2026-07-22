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

        // Rule 2: skip assignment_ids that already exist in DB
        $assignmentIds = $records->pluck('assignment_id')->unique()->values();

        $existingIds = DB::table('enumeration_business')
            ->whereIn('assignment_id', $assignmentIds)
            ->pluck('assignment_id')
            ->all();

        $existingIds = array_flip($existingIds); // fast lookup

        $inserted = 0;
        $skippedExisting = 0;
        $skippedNoGeo = 0;

        foreach ($records as $r) {
            if (isset($existingIds[$r['assignment_id']])) {
                $skippedExisting++;
                continue;
            }

            if (!is_numeric($r['geotag_latitude']) || !is_numeric($r['geotag_longitude'])) {
                $skippedNoGeo++;
                continue;
            }

            $record = [
                'valid_latitude'  => (float) $r['geotag_latitude'],
                'valid_longitude' => (float) $r['geotag_longitude'],
            ];

            DB::table('enumeration_business')->insert([
                'id'             => (string) Str::uuid(),
                'name'           => $r['nama_principal'] ?? null,
                'assignment_id'  => $r['assignment_id'],
                'latitude'       => $record['valid_latitude'],
                'longitude'      => $record['valid_longitude'],
                'original_area'  => $r['level_6_full_code'],
                'regency_id'     => null,
                'subdistrict_id' => null,
                'village_id'     => null,
                'sls_id'         => null,
                'coordinate'     => DB::raw(
                    "ST_SRID(POINT({$record['valid_longitude']}, {$record['valid_latitude']}), 4326)"
                ),
                'created_at'     => $now,
                'updated_at'     => $now,
            ]);

            // Prevent duplicate assignment_ids within the same batch too
            $existingIds[$r['assignment_id']] = true;

            $inserted++;
        }

        Log::info("CSV chunk import: inserted={$inserted}, skipped_existing={$skippedExisting}, skipped_no_geo={$skippedNoGeo}");
    }

    public function failed(Throwable $e): void
    {
        Log::error('EnumerationBusinessJob failed: ' . $e->getMessage());
    }
}
