<?php

namespace App\Http\Controllers;

use App\Models\EnumerationBusiness;
use App\Models\Sls;
use App\Models\User;
use App\Models\UserSlsCensus;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MoveController extends Controller
{
    use ApiResponser;

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
        | ENUMERATION BUSINESSES (ALL COLUMNS)
        |--------------------------------------------------------------------------
        */

        $user = User::find(Auth::id());
        $isAllowedRawData = $user->is_allowed_raw_data
            ? true
            : UserSlsCensus::where('user_id', $user->id)->where('sls_id', $slsId)->exists();
        $enumerationBusinesses = EnumerationBusiness::with(['regency', 'subdistrict', 'village', 'sls'])
            ->where('original_area', 'like', substr($sls->long_code, 0, 14) . '%')
            ->get()
            ->map(function ($business) use ($isAllowedRawData) {
                if (!$isAllowedRawData) {
                    $business->name = $business->building_number ?? '******';
                }
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
                $business->can_move = $isAllowedRawData;
                return $business;
            });

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
            'businesses' => $enumerationBusinesses,
        ], 'Businesses retrieved successfully');
    }
}
