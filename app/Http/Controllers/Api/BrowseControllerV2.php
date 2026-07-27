<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgricultureBusiness;
use App\Models\EnumerationBusiness;
use App\Models\MarketBusiness;
use App\Models\SbrBusiness;
use App\Models\Sls;
use App\Models\SupplementBusiness;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrowseControllerV2 extends Controller
{
    use ApiResponser;

    public function getBusinessInBoundingBox(Request $request)
    {
        // ✅ Validate query parameters
        $request->validate([
            'min_lat' => 'required|numeric', // latitude of SW corner
            'max_lat' => 'required|numeric', // latitude of NE corner
            'min_lng' => 'required|numeric', // longitude of SW corner
            'max_lng' => 'required|numeric', // longitude of NE corner
        ]);

        // ✅ Read the input values directly
        $minLat = $request->input('min_lat'); // bottom side (southern latitude)
        $maxLat = $request->input('max_lat'); // top side (northern latitude)
        $minLng = $request->input('min_lng'); // left side (western longitude)
        $maxLng = $request->input('max_lng'); // right side (eastern longitude)

        $polygonWkt = sprintf(
            'POLYGON((%s %s, %s %s, %s %s, %s %s, %s %s))',
            $minLng,
            $minLat,
            $maxLng,
            $minLat,
            $maxLng,
            $maxLat,
            $minLng,
            $maxLat,
            $minLng,
            $minLat
        );

        $now = now();
        $marketQuery = MarketBusiness::with(['user', 'market', 'regency', 'subdistrict', 'village', 'sls']);

        $marketBusinesses = $marketQuery
            ->whereRaw(
                "MBRContains(ST_PolygonFromText(?, 4326, 'axis-order=long-lat'), coordinate)",
                [$polygonWkt]
            )
            ->get()
            ->map(function ($business) use ($now) {
                $business->project = [
                    'id' => 'swmaps market',
                    'name' => 'Sentra Ekonomi SWMaps',
                    'type' => 'swmaps market',
                    'description' => $business->market->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                return $business;
            });

        $supplementSwmapsBusinesses = SupplementBusiness::with(['user', 'regency', 'subdistrict', 'village', 'sls'])
            ->whereRaw(
                "MBRContains(ST_PolygonFromText(?, 4326, 'axis-order=long-lat'), coordinate)",
                [$polygonWkt]
            )
            ->get()
            ->map(function ($business) use ($now) {
                $business->project = [
                    'id' => 'kendedes mobile',
                    'name' => 'Kendedes Mobile',
                    'type' => 'kendedes mobile',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                return $business;
            });

        $statusMap = [
            99 => 'Tidak ditemukan',
            1  => 'Ditemukan',
            3  => 'Tutup',
            4  => 'Ganda',
        ];

        $sbrBusinesses = SbrBusiness::with(['regency', 'subdistrict', 'village', 'sls'])
            ->where('status_sbr', '=', 1)
            ->whereRaw(
                "MBRContains(ST_PolygonFromText(?, 4326, 'axis-order=long-lat'), coordinate)",
                [$polygonWkt]
            )
            ->get()
            ->map(function ($business) use ($statusMap) {
                $label = $statusMap[$business->status_sbr] ?? 'Unknown';
                $business->description = "Status SBR: {$business->status_sbr} ({$label})";
                $business->project = [
                    'id' => 'sbr',
                    'name' => 'SBR Matchapro',
                    'type' => 'sbr',
                    'description' => null,
                    'created_at' => '2024-06-28 10:15:30',
                    'updated_at' => '2024-06-28 10:15:30',
                ];
                $business->user =  [
                    'id' => 'dummy-sbr',
                    'firstname' => 'SBR Matchapro',
                    'email' => 'dummy@example.com',
                ];
                $business->is_locked = true;
                return $business;
            });

        $enumerationBusinesses = EnumerationBusiness::with(['regency', 'subdistrict', 'village', 'sls'])
            ->whereRaw(
                "MBRContains(ST_PolygonFromText(?, 4326, 'axis-order=long-lat'), coordinate)",
                [$polygonWkt]
            )
            ->get()
            ->map(function ($business) {
                $business->name = '*****';
                $business->description = "Hasil Pencacahan SE2026";
                $business->project = [
                    'id' => 'enumeration',
                    'name' => 'Hasil Pencacahan',
                    'type' => 'enumeration',
                    'description' => null,
                    'created_at' => '2024-06-28 10:15:30',
                    'updated_at' => '2024-06-28 10:15:30',
                ];
                $business->user =  [
                    'id' => 'dummy-enumeration',
                    'firstname' => 'Petugas SE2026',
                    'email' => 'dummy@example.com',
                ];
                $business->is_locked = true;
                return $business;
            });

        $agricultureBusinesses = AgricultureBusiness::with(['regency', 'subdistrict', 'village', 'sls'])
            ->whereRaw(
                "MBRContains(ST_PolygonFromText(?, 4326, 'axis-order=long-lat'), coordinate)",
                [$polygonWkt]
            )
            ->get()
            ->map(function ($business) use ($statusMap) {
                $business->project = [
                    'id' => 'agriculture',
                    'name' => 'ST2023 Wilkerstat',
                    'type' => 'agriculture',
                    'description' => null,
                    'created_at' => '2024-06-28 10:15:30',
                    'updated_at' => '2024-06-28 10:15:30',
                ];
                $business->user =  [
                    'id' => 'dummy-agriculture',
                    'firstname' => 'ST2023 Wilkerstat',
                    'email' => 'dummy@example.com',
                ];
                $business->is_locked = true;
                return $business;
            });


        $combinedBusiness = $marketBusinesses->merge($supplementSwmapsBusinesses)
            ->merge($sbrBusinesses)->merge($enumerationBusinesses)->merge($agricultureBusinesses);

        return $this->successResponse($combinedBusiness, 'Businesses retrieved successfully');
    }

    public function getBusinessBySls(Request $request)
    {
        $request->validate([
            'sls' => 'required|exists:sls,id',
        ]);

        $slsId = $request->input('sls');

        /*
        |--------------------------------------------------------------------------
        | GET SLS (SAFE FORMAT)
        |--------------------------------------------------------------------------
        */

        $sls = Sls::withoutGlobalScopes()
            ->with([
                'village.subdistrict.regency'
            ])
            ->where('id', $slsId)
            ->selectRaw('
                    id,
                    village_id,
                    name,
                    short_code,
                    long_code,

                    ST_AsText(sls.geom) as geom_wkt,
                    ST_AsGeoJSON(sls.geom) as geom_geojson
            ')
            ->first();

        if (!$sls) {
            return $this->errorResponse('Geojson SLS tidak ditemukan', 404);
        }

        $now = now();

        /*
        |--------------------------------------------------------------------------
        | MARKET BUSINESSES (ALL COLUMNS)
        |--------------------------------------------------------------------------
        */

        $marketBusinesses = MarketBusiness::with(['user', 'market', 'regency', 'subdistrict', 'village', 'sls'])
            ->whereRaw(
                'ST_Intersects(
                    coordinate,
                    ST_GeomFromText(?, 4326)
                )',
                [$sls->geom_wkt]
            )
            ->get()
            ->map(function ($business) use ($now) {

                $business->project = [
                    'id' => 'swmaps-market',
                    'name' => 'Sentra Ekonomi SWMaps',
                    'type' => 'swmaps market',
                    'description' => optional($business->market)->name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                return $business;
            });

        /*
        |--------------------------------------------------------------------------
        | SUPPLEMENT BUSINESSES (ALL COLUMNS)
        |--------------------------------------------------------------------------
        */

        $supplementBusinesses = SupplementBusiness::with(['user', 'regency', 'subdistrict', 'village', 'sls'])
            ->whereRaw(
                'ST_Intersects(
                    coordinate,
                    ST_GeomFromText(?, 4326)
                )',
                [$sls->geom_wkt]
            )
            ->get()
            ->map(function ($business) use ($now) {

                $business->project = [
                    'id' => 'kendedes-mobile',
                    'name' => 'Kendedes Mobile',
                    'type' => 'kendedes mobile',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                return $business;
            });

        /*
        |--------------------------------------------------------------------------
        | SBR BUSINESSES (ALL COLUMNS)
        |--------------------------------------------------------------------------
        */

        $statusMap = [
            99 => 'Tidak ditemukan',
            1  => 'Ditemukan',
            3  => 'Tutup',
            4  => 'Ganda',
        ];

        $sbrBusinesses = SbrBusiness::with(['regency', 'subdistrict', 'village', 'sls'])
            ->where('status_sbr', '=', 1)
            ->whereRaw(
                'ST_Intersects(
                    coordinate,
                    ST_GeomFromText(?, 4326)
                )',
                [$sls->geom_wkt]
            )
            ->get()
            ->map(function ($business) use ($statusMap) {
                $label = $statusMap[$business->status_sbr] ?? 'Unknown';
                $business->description = "Status SBR: {$business->status_sbr} ({$label})";
                $business->project = [
                    'id' => 'sbr',
                    'name' => 'SBR Matchapro',
                    'type' => 'sbr',
                    'description' => null,
                    'created_at' => '2024-06-28 10:15:30',
                    'updated_at' => '2024-06-28 10:15:30',
                ];
                $business->user =  [
                    'id' => 'dummy-sbr',
                    'firstname' => 'SBR Matchapro',
                    'email' => 'dummy@example.com',
                ];
                $business->is_locked = true;
                return $business;
            });

        /*
        |--------------------------------------------------------------------------
        | ENUMERATION BUSINESSES (ALL COLUMNS)
        |--------------------------------------------------------------------------
        */

        $enumerationBusinesses = EnumerationBusiness::with(['regency', 'subdistrict', 'village', 'sls'])
            ->whereRaw(
                'ST_Intersects(
                    coordinate,
                    ST_GeomFromText(?, 4326)
                )',
                [$sls->geom_wkt]
            )
            ->get()
            ->map(function ($business) {
                $business->name = '*****';
                $business->description = "Hasil Pencacahan SE2026";
                $business->project = [
                    'id' => 'enumeration',
                    'name' => 'Hasil Pencacahan',
                    'type' => 'enumeration',
                    'description' => null,
                    'created_at' => '2024-06-28 10:15:30',
                    'updated_at' => '2024-06-28 10:15:30',
                ];
                $business->user =  [
                    'id' => 'dummy-enumeration',
                    'firstname' => 'Petugas SE2026',
                    'email' => 'dummy@example.com',
                ];
                $business->is_locked = true;
                return $business;
            });

        /*
        |--------------------------------------------------------------------------
        | AGRICULTURE BUSINESSES (ALL COLUMNS)
        |--------------------------------------------------------------------------
        */

        $agricultureBusinesses = AgricultureBusiness::with(['regency', 'subdistrict', 'village', 'sls'])
            ->whereRaw(
                'ST_Intersects(
                    coordinate,
                    ST_GeomFromText(?, 4326)
                )',
                [$sls->geom_wkt]
            )
            ->get()
            ->map(function ($business) {
                $business->project = [
                    'id' => 'agriculture',
                    'name' => 'ST2023 Wilkerstat',
                    'type' => 'agriculture',
                    'description' => null,
                    'created_at' => '2024-06-28 10:15:30',
                    'updated_at' => '2024-06-28 10:15:30',
                ];
                $business->user =  [
                    'id' => 'dummy-agriculture',
                    'firstname' => 'ST2023 Wilkerstat',
                    'email' => 'dummy@example.com',
                ];
                $business->is_locked = true;
                return $business;
            });

        $combinedBusiness = $marketBusinesses->merge($supplementBusinesses)
            ->merge($sbrBusinesses)
            ->merge($agricultureBusinesses)->merge($enumerationBusinesses);

        /*
        |--------------------------------------------------------------------------
        | FINAL RESPONSE
        |--------------------------------------------------------------------------
        */
        return $this->successResponse([
            'sls' => [
                'id' => $sls->id,
                'name' => $sls->name,
                'short_code' => $sls->short_code,
                'long_code' => $sls->long_code,
                'village_id' => $sls->village_id,

                'village' => [
                    'id' => $sls->village->id,
                    'name' => $sls->village->name,
                    'short_code' => $sls->village->short_code,
                    'long_code' => $sls->village->long_code,
                    'subdistrict_id' => $sls->village->subdistrict_id,

                    'subdistrict' => [
                        'id' => $sls->village->subdistrict->id,
                        'name' => $sls->village->subdistrict->name,
                        'short_code' => $sls->village->subdistrict->short_code,
                        'long_code' => $sls->village->subdistrict->long_code,
                        'regency_id' => $sls->village->subdistrict->regency_id,

                        'regency' => [
                            'id' => $sls->village->subdistrict->regency->id,
                            'name' => $sls->village->subdistrict->regency->name,
                            'short_code' => $sls->village->subdistrict->regency->short_code,
                            'long_code' => $sls->village->subdistrict->regency->long_code,
                        ]
                    ]
                ],

                'geojson' => json_decode($sls->geom_geojson),

            ],
            'businesses' => $combinedBusiness,
        ], 'Businesses retrieved successfully');
    }

    public function findSlsByCoordinates(Request $request)
    {
        $validated = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        $point = "POINT({$validated['lng']} {$validated['lat']})";

        $sls = Sls::query()
            ->with([
                'village.subdistrict.regency'
            ])
            ->whereRaw(
                "ST_Contains(geom, ST_GeomFromText(?, 4326, 'axis-order=long-lat'))",
                [$point]
            )->first();

        if (!$sls) {
            return $this->errorResponse('Tidak ditemukan SLS yang cocok', 404);
        }

        return $this->successResponse([
            'sls' => $sls,
        ], 'SLS retrieved successfully');
    }

    public function checkBusinessDataUpdate(Request $request)
    {
        $payload = $request->all();
        $slsIds = collect($payload)->pluck('sls_id')->unique()->values()->all();

        /*
    |--------------------------------------------------------------------------
    | STEP 1: FAST PASS — count by sls_id column (cheap, indexed)
    |--------------------------------------------------------------------------
    */

        $placeholders = implode(',', array_fill(0, count($slsIds), '?'));

        $fastCounts = DB::select("
        SELECT sls_id, SUM(cnt) as total_count
        FROM (
            SELECT sls_id, COUNT(*) as cnt FROM supplement_business WHERE sls_id IN ($placeholders) GROUP BY sls_id
            UNION ALL
            SELECT sls_id, COUNT(*) as cnt FROM market_business WHERE sls_id IN ($placeholders) GROUP BY sls_id
            UNION ALL
            SELECT sls_id, COUNT(*) as cnt FROM agriculture_business WHERE sls_id IN ($placeholders) GROUP BY sls_id
            UNION ALL
            SELECT sls_id, COUNT(*) as cnt FROM sbr_business WHERE sls_id IN ($placeholders) AND status_sbr = 1 GROUP BY sls_id
            UNION ALL
            SELECT sls_id, COUNT(*) as cnt FROM enumeration_business WHERE sls_id IN ($placeholders) GROUP BY sls_id
        ) as combined
        GROUP BY sls_id
    ", array_merge($slsIds, $slsIds, $slsIds, $slsIds, $slsIds));

        $fastCountMap = collect($fastCounts)->keyBy('sls_id');

        /*
    |--------------------------------------------------------------------------
    | STEP 2: SPLIT — figure out which sls_ids look like they need_update
    |--------------------------------------------------------------------------
    */

        $needsSpatialCheck = [];
        $fastResults = [];

        foreach ($payload as $item) {
            $fastCount = (int) ($fastCountMap->get($item['sls_id'])->total_count ?? 0);
            $reported = (int) $item['business_count'];

            if ($fastCount === $reported) {
                // Fast path agrees it's up to date — trust it, skip spatial check
                $fastResults[$item['sls_id']] = [
                    'sls_id' => $item['sls_id'],
                    'need_update' => false,
                    'actual_count' => $fastCount,
                    'reported_count' => $reported,
                ];
            } else {
                // Mismatch — could be a real change, OR a false positive from
                // unmatched sls_id rows. Needs the accurate spatial check.
                $needsSpatialCheck[] = $item;
            }
        }

        /*
    |--------------------------------------------------------------------------
    | STEP 3: SLOW PASS — spatial ST_Intersects, only for flagged sls
    |--------------------------------------------------------------------------
    */

        if (!empty($needsSpatialCheck)) {
            $spatialSlsIds = collect($needsSpatialCheck)->pluck('sls_id')->unique()->values()->all();

            $slsList = Sls::withoutGlobalScopes()
                ->whereIn('id', $spatialSlsIds)
                ->selectRaw('id, ST_AsText(geom) as geom_wkt')
                ->get()
                ->keyBy('id');

            foreach ($needsSpatialCheck as $item) {
                $sls = $slsList->get($item['sls_id']);
                $reported = (int) $item['business_count'];

                if (!$sls) {
                    $fastResults[$item['sls_id']] = [
                        'sls_id' => $item['sls_id'],
                        'need_update' => true,
                        'actual_count' => 0,
                        'reported_count' => $reported,
                    ];
                    continue;
                }

                $wkt = $sls->geom_wkt;
                $intersects = 'ST_Intersects(coordinate, ST_GeomFromText(?, 4326))';

                $actualCount =
                    SupplementBusiness::whereRaw($intersects, [$wkt])->count() +
                    MarketBusiness::whereRaw($intersects, [$wkt])->count() +
                    AgricultureBusiness::whereRaw($intersects, [$wkt])->count() +
                    SbrBusiness::where('status_sbr', 1)->whereRaw($intersects, [$wkt])->count() +
                    EnumerationBusiness::whereRaw($intersects, [$wkt])->count();

                // Overwrite the fast-path guess with the authoritative spatial result
                $fastResults[$item['sls_id']] = [
                    'sls_id' => $item['sls_id'],
                    'need_update' => $actualCount !== $reported,
                    'actual_count' => $actualCount,
                    'reported_count' => $reported,
                ];
            }
        }

        /*
    |--------------------------------------------------------------------------
    | STEP 4: RETURN IN ORIGINAL PAYLOAD ORDER
    |--------------------------------------------------------------------------
    */

        $result = collect($payload)->map(function ($item) use ($fastResults) {
            return $fastResults[$item['sls_id']];
        })->values();

        return $this->successResponse($result, 'SLS retrieved successfully');
    }
}
