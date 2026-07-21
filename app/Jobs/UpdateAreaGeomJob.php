<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateAreaGeomJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 0;

    protected $connection2;
    protected $table;
    protected $areaType;
    protected $periodVersions;
    protected $rows;

    /**
     * Create a new job instance.
     *
     * @param string $connection Database connection name to update.
     * @param string $table Table name (regencies, subdistricts, villages, sls).
     * @param string $areaType Folder name under the geojson storage path.
     * @param array $periodVersions Map of area_period_id => period_version for this connection.
     * @param array $rows Rows (id, long_code, area_period_id) needing a geom update.
     */
    public function __construct($connection, $table, $areaType, $periodVersions, $rows)
    {
        // Named connection2 to avoid clashing with the Queueable trait's $connection property.
        $this->connection2 = $connection;
        $this->table = $table;
        $this->areaType = $areaType;
        $this->periodVersions = $periodVersions;
        $this->rows = $rows;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $updated = 0;
        $skipped = 0;

        DB::connection($this->connection2)->transaction(function () use (&$updated, &$skipped) {
            foreach ($this->rows as $row) {
                $periodVersion = $this->periodVersions[$row->area_period_id] ?? null;
                if (!$periodVersion) {
                    $skipped++;
                    continue;
                }

                $longCode = (string) $row->long_code;
                if ($longCode === '') {
                    $skipped++;
                    continue;
                }

                if ($this->table === 'sls') {
                    $first7 = substr($longCode, 0, 7);
                    $first14 = substr($longCode, 0, 14);
                    $path = storage_path(
                        "app/private/geojson/{$periodVersion}/{$this->areaType}/{$first7}/{$first14}.geojson"
                    );
                } else {
                    $path = storage_path(
                        "app/private/geojson/{$periodVersion}/{$this->areaType}/{$longCode}.geojson"
                    );
                }

                if (!is_file($path)) {
                    $skipped++;
                    continue;
                }

                $json = file_get_contents($path);
                if ($json === false) {
                    $skipped++;
                    continue;
                }

                $data = json_decode($json, true);
                if (!is_array($data)) {
                    $skipped++;
                    continue;
                }

                $geometry = null;
                if (isset($data['type']) && $data['type'] === 'Feature') {
                    $geometry = $data['geometry'] ?? null;
                } elseif (isset($data['type']) && $data['type'] === 'FeatureCollection') {
                    $features = $data['features'] ?? [];
                    $first = is_array($features) && count($features) > 0 ? $features[0] : null;
                    $geometry = is_array($first) ? ($first['geometry'] ?? null) : null;
                } elseif (isset($data['type']) && isset($data['coordinates'])) {
                    $geometry = $data;
                }

                if (!is_array($geometry)) {
                    $skipped++;
                    continue;
                }

                $geometryJson = json_encode($geometry);
                if ($geometryJson === false) {
                    $skipped++;
                    continue;
                }

                DB::connection($this->connection2)->statement(
                    "UPDATE {$this->table}
                     SET geom = ST_SRID(ST_GeomFromGeoJSON(?), 4326)
                     WHERE id = ?",
                    [$geometryJson, $row->id]
                );
                $updated++;
            }
        });

        Log::info("UpdateAreaGeomJob: [{$this->connection2}] {$this->table} batch completed. Updated: {$updated}, Skipped: {$skipped}");
    }
}
