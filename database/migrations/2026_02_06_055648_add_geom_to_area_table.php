<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = [
            'regencies' => 'regency',
            'subdistricts' => 'subdistrict',
            'villages' => 'village',
            'sls' => 'sls_by_subdistrict',
        ];
        $chunk = 5000;

        foreach ($tables as $table => $areaType) {
            DB::statement(
                "ALTER TABLE {$table}
             ADD COLUMN geom MULTIPOLYGON SRID 4326 NULL"
            );
        }

        $periodVersions = DB::table('area_periods')
            ->pluck('period_version', 'id')
            ->all();

        foreach ($tables as $table => $areaType) {
            DB::table($table)
                ->whereNull('geom')
                ->whereNotNull('long_code')
                ->whereNotNull('area_period_id')
                ->chunkById($chunk, function ($rows) use ($table, $areaType, $periodVersions) {
                    DB::transaction(function () use ($rows, $table, $areaType, $periodVersions) {
                        foreach ($rows as $row) {
                            $periodVersion = $periodVersions[$row->area_period_id] ?? null;
                            if (!$periodVersion) {
                                continue;
                            }

                            $longCode = (string) $row->long_code;
                            if ($longCode === '') {
                                continue;
                            }

                            if ($table === 'sls') {
                                $first7 = substr($longCode, 0, 7);
                                $first14 = substr($longCode, 0, 14);
                                $path = storage_path(
                                    "app/private/geojson/{$periodVersion}/{$areaType}/{$first7}/{$first14}.geojson"
                                );
                            } else {
                                $path = storage_path(
                                    "app/private/geojson/{$periodVersion}/{$areaType}/{$longCode}.geojson"
                                );
                            }

                            if (!is_file($path)) {
                                continue;
                            }

                            $json = file_get_contents($path);
                            if ($json === false) {
                                continue;
                            }

                            $data = json_decode($json, true);
                            if (!is_array($data)) {
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
                                continue;
                            }

                            $geometryJson = json_encode($geometry);
                            if ($geometryJson === false) {
                                continue;
                            }

                            DB::statement(
                                "UPDATE {$table}
                             SET geom = ST_SRID(ST_GeomFromGeoJSON(?), 4326)
                             WHERE id = ?",
                                [$geometryJson, $row->id]
                            );
                        }
                    });
                });
        }
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE regencies DROP COLUMN geom");
        DB::statement("ALTER TABLE subdistricts DROP COLUMN geom");
        DB::statement("ALTER TABLE villages DROP COLUMN geom");
        DB::statement("ALTER TABLE sls DROP COLUMN geom");
    }
};
