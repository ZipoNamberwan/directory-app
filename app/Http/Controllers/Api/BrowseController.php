<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketBusiness;
use App\Models\Project;
use App\Models\Sls;
use App\Models\SupplementBusiness;
use App\Models\SurveyBusiness;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class BrowseController extends Controller
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

        // $project = [
        //     'id' => 'survey',
        //     'name' => 'Survey Project',
        //     'type' => 'survey',
        //     'description' => null,
        //     'created_at' => '2024-06-28 10:15:30',
        //     'updated_at' => '2024-06-28 10:15:30',
        // ];
        // $dummyUser = [
        //     'id' => 'dummy-user',
        //     'firstname' => 'Survei BPS',
        //     'email' => 'dummy@example.com',
        // ];
        // $surveyBusinesses = SurveyBusiness::with(['survey'])
        //     ->whereBetween('latitude', [$minLat, $maxLat])
        //     ->whereBetween('longitude', [$minLng, $maxLng])
        //     ->get()
        //     ->map(function ($business) use ($project, $dummyUser) {
        //         $business->project = $project;
        //         $business->user = $dummyUser;
        //         $business->is_locked = true;
        //         return $business;
        //     });

        $combinedBusiness = $marketBusinesses->merge($supplementSwmapsBusinesses)
            /* ->merge($surveyBusinesses) */;

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

        $combinedBusiness = $marketBusinesses->merge($supplementBusinesses);

        /*
        |--------------------------------------------------------------------------
        | FINAL RESPONSE
        |--------------------------------------------------------------------------
        */
        return $this->successResponse([
            'sls' => [
                'id' => $sls->id,
                'name'=> $sls->name,
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
}
