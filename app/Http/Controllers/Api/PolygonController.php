<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sls;
use App\Models\Village;
use App\Models\Subdistrict;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class PolygonController extends Controller
{
    use ApiResponser;

    public function getVillagesBySubdistrict($subdistrict)
    {
        $subdistrict = Subdistrict::where('long_code', $subdistrict)->first();
        if (!$subdistrict) {
            return $this->errorResponse('Desa tidak ditemukan.', 404);
        }
        $villages = Village::where('subdistrict_id', $subdistrict->id)->get();
        return $this->successResponse($villages);
    }

    public function getSlsByVillage($village)
    {
        $sls = Sls::where('village_id', $village)->get();
        return $this->successResponse($sls);
    }

    public function downloadPolygonData(Request $request)
    {
        $id = $request->input('id');
        $type = $request->input('type');

        if (!$id || !$type) {
            return $this->errorResponse('Invalid request.', 400);
        }

        if (!in_array($type, ['village', 'sls'], true)) {
            return $this->errorResponse('Invalid type specified.', 400);
        }

        $code = null;
        $version = null;
        $relativePath = null;

        if ($type === 'village') {
            $village = Village::with('period')->find($id);
            if (!$village) {
                return $this->errorResponse('Desa tidak ditemukan.', 404);
            }

            $code = $village->long_code;
            $version = $village->period?->period_version;
            $relativePath = "village/{$code}.geojson";
        } else {
            $sls = Sls::with('period')->find($id);
            if (!$sls) {
                return $this->errorResponse('SLS tidak ditemukan.', 404);
            }

            $code = substr($sls->long_code, 0, 14);
            $version = $sls->period?->period_version;
            $subdistrictCode = substr($code, 0, 7);
            $relativePath = "sls_by_subdistrict/{$subdistrictCode}/{$code}.geojson";
        }

        if (!$code || !$version || !$relativePath) {
            return $this->errorResponse('Polygon tidak ditemukan.', 404);
        }

        $fileName = "{$code}.geojson";
        $filePath = storage_path("app/private/geojson/{$version}/{$relativePath}");

        if (!is_file($filePath)) {
            return $this->errorResponse('Polygon tidak ditemukan.', 404);
        }

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/geo+json',
        ]);
    }
}
