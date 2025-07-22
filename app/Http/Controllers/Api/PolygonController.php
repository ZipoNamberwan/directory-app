<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sls;
use App\Models\Village;
use App\Traits\ApiResponser;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;

class PolygonController extends Controller
{
    use ApiResponser;

    public function getVillagesBySubdistrict($subdistrict)
    {
        $villages = Village::where('subdistrict_id', $subdistrict)->get();
        return $this->successResponse($villages);
    }

    public function getSlsByVillage($village)
    {
        $sls = Sls::where('village_id', $village)->get();
        return $this->successResponse($sls);
    }

    public function downloadPolygonData(Request $request)
    {
        $id = $request->id;
        $type = $request->type;

        if (!in_array($type, ['village', 'sls'])) {
            return $this->errorResponse('Invalid type specified.', 400);
        }

        if ($type === 'village') {
            $village = Village::find($id);
            if (!$village) {
                return $this->errorResponse('Desa tidak ditemukan.', 404);
            }
        }

        if ($type === 'sls') {
            $sls = Sls::find($id);
            if (!$sls) {
                return $this->errorResponse('SLS tidak ditemukan.', 404);
            }

            $id = substr($sls->id, 0, 14);
        }

        $fileName = "{$id}.geojson";
        $filePath = storage_path("app/private/geojson/{$fileName}");

        if (!file_exists($filePath)) {
            return $this->errorResponse('Polygon tidak ditemukan.', 404);
        }

        return response()->download($filePath, $fileName, [
            'Content-Type' => 'application/geo+json',
        ]);
    }
}
