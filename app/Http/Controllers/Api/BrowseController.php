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

        $project = Project::where('type', 'swmaps market')->first();

        $marketQuery = MarketBusiness::with(['user']);
        if ($project != null) {
            $marketQuery->with(['market']);
        }

        $marketBusinesses = $marketQuery
            ->whereRaw(
                "MBRContains(ST_PolygonFromText(?, 4326, 'axis-order=long-lat'), coordinate)",
                [$polygonWkt]
            )
            ->get()
            ->map(function ($business) use ($project) {
                if ($project != null) {
                    $business->project = [
                        'id' => $project->id,
                        'name' => $project->name,
                        'type' => $project->type,
                        'description' => $business->market->name,
                        'created_at' => $project->created_at,
                        'updated_at' => $project->updated_at,
                    ];
                } else {
                    $business->project = null;
                }
                return $business;
            });

        $supplementSwmapsBusinesses = SupplementBusiness::with(['project', 'user'])
            ->whereRaw(
                "MBRContains(ST_PolygonFromText(?, 4326, 'axis-order=long-lat'), coordinate)",
                [$polygonWkt]
            )
            ->get();

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
            /* ->merge($surveyBusinesses) */ ;

        return $this->successResponse($combinedBusiness, 'Businesses retrieved successfully');


    }

    public function getBusinessBySls(Request $request)
    {
        $request->validate([
            'sls' => 'required|exists:sls,id',
        ]);

        $slsId = $request->input('sls');

        $slsGeom = Sls::withoutGlobalScopes()->where('id', $slsId)->value('geom');
        if ($slsGeom === null) {
            return $this->errorResponse('Geojson SLS tidak ditemukan', 404);
        }

        $bufferMeters = 30;
        $earthRadiusMeters = 6371000;
        $bufferRadians = $bufferMeters / $earthRadiusMeters;
        $bufferDegrees = rad2deg($bufferRadians);

        $project = Project::where('type', 'swmaps market')->first();

        $marketQuery = MarketBusiness::with(['user']);
        if ($project != null) {
            $marketQuery->with(['market']);
        }

        $marketBusinesses = $marketQuery
            ->whereRaw(
                'ST_Intersects(coordinate, ST_Buffer(ST_GeomFromWKB(?), ?))',
                [$slsGeom, $bufferDegrees]
            )
            ->get()
            ->map(function ($business) use ($project) {
                if ($project != null) {
                    $business->project = [
                        'id' => $project->id,
                        'name' => $project->name,
                        'type' => $project->type,
                        'description' => $business->market->name,
                        'created_at' => $project->created_at,
                        'updated_at' => $project->updated_at,
                    ];
                } else {
                    $business->project = null;
                }
                return $business;
            });

        $supplementSwmapsBusinesses = SupplementBusiness::with(['project', 'user'])
            ->whereRaw(
                'ST_Intersects(coordinate, ST_Buffer(ST_GeomFromWKB(?), ?))',
                [$slsGeom, $bufferDegrees]
            )
            ->get();

        $combinedBusiness = $marketBusinesses->merge($supplementSwmapsBusinesses);

        return $this->successResponse($combinedBusiness, 'Businesses retrieved successfully');
    }
}
