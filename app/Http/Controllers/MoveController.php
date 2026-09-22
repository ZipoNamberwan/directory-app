<?php

namespace App\Http\Controllers;

use App\Models\EnumerationBusiness;
use App\Models\Sls;
use Exception;
use App\Models\User;
use App\Models\UserSlsCensus;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    public function checkBusinessDataUpdate(Request $request)
    {
        $payload = $request->all();
        $slsIds = collect($payload)->pluck('sls_id')->unique()->values()->all();

        // enumeration_business is matched by the first 14 chars of original_area
        // against the sls's long_code prefix, not by sls_id.
        $slsLongCodes = Sls::withoutGlobalScopes()->whereIn('id', $slsIds)->pluck('long_code', 'id');

        $countMap = collect();

        if ($slsLongCodes->isNotEmpty()) {
            $subqueries = [];
            $bindings = [];

            foreach ($slsLongCodes as $slsId => $longCode) {
                $subqueries[] = 'SELECT ? as sls_id, COUNT(*) as cnt FROM enumeration_business WHERE original_area LIKE ?';
                $bindings[] = $slsId;
                $bindings[] = substr($longCode, 0, 14) . '%';
            }

            $counts = DB::select(implode(' UNION ALL ', $subqueries), $bindings);
            $countMap = collect($counts)->keyBy('sls_id');
        }

        $result = collect($payload)->map(function ($item) use ($countMap) {
            $actualCount = (int) ($countMap->get($item['sls_id'])->cnt ?? 0);
            $reported = (int) $item['business_count'];

            return [
                'sls_id' => $item['sls_id'],
                'need_update' => $actualCount !== $reported,
                'actual_count' => $actualCount,
                'reported_count' => $reported,
            ];
        })->values();

        return $this->successResponse($result, 'SLS retrieved successfully');
    }

    public function updateLocation(Request $request, string $id)
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        try {
            $business = EnumerationBusiness::find($id);
            if (!$business) {
                return $this->errorResponse('Data enumerasi tidak ditemukan', 404);
            }

            $business->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,

                // always regenerated
                'coordinate' => DB::raw(
                    "ST_PointFromText('POINT({$request->longitude} {$request->latitude})', 4326, 'axis-order=long-lat')"
                ),
            ]);
            $business->refresh();

            return $this->successResponse(data: $business, status: 200);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui tagging', 500);
        }
    }
}
