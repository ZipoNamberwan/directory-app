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
        $chunk = 1000;

        $periodVersions = DB::table('area_periods')
            ->pluck('period_version', 'id')
            ->all();

        // 1. Fill in any geom that is still NULL, using the same geojson lookup
        // logic as the original migration (covers files added since then).
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

        // 2. Anything still NULL has no geojson available at all -> fill with a
        // placeholder geometry so the column can become NOT NULL.
        //
        // Note: MySQL's WKT parser does not support the "EMPTY" keyword for
        // MULTIPOLYGON (only GEOMETRYCOLLECTION() is a valid empty literal,
        // and that type can't be stored in a MULTIPOLYGON column), so
        // 'MULTIPOLYGON EMPTY' raises error 3037 (ER_GIS_INVALID_DATA).
        // Instead we use a degenerate, tiny sliver polygon anchored at
        // (0, 0) that will never realistically match a real ST_Contains
        // lookup. The 'axis-order=long-lat' option keeps this consistent
        // with ST_GeomFromGeoJSON above, which already interprets
        // coordinates as [lon, lat].
        foreach ($tables as $table => $areaType) {
            DB::statement(
                "UPDATE {$table}
                 SET geom = ST_GeomFromText(
                     'MULTIPOLYGON(((0 0, 0 0.0000001, 0.0000001 0.0000001, 0.0000001 0, 0 0)))',
                     4326,
                     'axis-order=long-lat'
                 )
                 WHERE geom IS NULL"
            );
        }

        // 3. MySQL requires a NOT NULL column before it can carry a SPATIAL index,
        // so flip the column definition now that every row has a geometry.
        foreach ($tables as $table => $areaType) {
            DB::statement(
                "ALTER TABLE {$table}
                 MODIFY geom MULTIPOLYGON SRID 4326 NOT NULL"
            );
        }

        // 4. Add the spatial index.
        foreach ($tables as $table => $areaType) {
            DB::statement(
                "ALTER TABLE {$table}
                 ADD SPATIAL INDEX {$table}_geom_spatial (geom)"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'regencies' => 'regency',
            'subdistricts' => 'subdistrict',
            'villages' => 'village',
            'sls' => 'sls_by_subdistrict',
        ];

        foreach ($tables as $table => $areaType) {
            DB::statement("ALTER TABLE {$table} DROP INDEX {$table}_geom_spatial");
            DB::statement("ALTER TABLE {$table} MODIFY geom MULTIPOLYGON SRID 4326 NULL");
        }
    }
};
