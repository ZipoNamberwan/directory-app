<?php

namespace App\Http\Controllers\Api;

use App\Helpers\DatabaseSelector;
use App\Http\Controllers\Controller;
use App\Models\SlsBusiness;
use App\Models\SlsUpdatePrelist;
use App\Models\Village;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WilkerstatController extends Controller
{
    use ApiResponser;

    public function getAssignments()
    {

        // Only eager load wilkerstatSls WITHOUT nested village
        $user = Auth::user();
        $sls = $user->wilkerstatSls;

        // Get unique village_ids from wilkerstatSls
        $villageIds = $sls->pluck('village_id')->unique()->values();

        // Fetch actual villages by ID
        $villages = Village::whereIn('id', $villageIds)->get();

        return $this->successResponse([
            'sls' => $sls,
            'villages' => $villages,
        ], 'Login successful');
    }

    public function getBusinessByVillage($villageId)
    {
        // Only eager load wilkerstatSls WITHOUT nested village
        $user = Auth::user();
        $sls = $user->wilkerstatSls;

        // Filter SLS by village_id
        $filteredSls = $sls->where('village_id', $villageId);

        if ($filteredSls->isEmpty()) {
            return $this->errorResponse('Tidak ada assignment untuk desa ini', 404);
        }

        $filteredSlsIds = $filteredSls->pluck('id');
        $businesses = SlsBusiness::on(DatabaseSelector::getConnection(substr($villageId, 0, 4)))
            ->whereIn('sls_id', $filteredSlsIds)
            // ->with(['sls', 'village'])
            ->get();

        return $this->successResponse($businesses, 'SLS retrieved successfully');
    }

    public function getBusinessBySls($slsId)
    {
        // Only eager load wilkerstatSls WITHOUT nested village
        $user = Auth::user();
        $sls = $user->wilkerstatSls;

        // Filter SLS by village_id
        $filteredSls = $sls->where('id', $slsId);

        if ($filteredSls->isEmpty()) {
            return $this->errorResponse('Tidak ada assignment untuk SLS ini', 404);
        }

        $businesses = SlsBusiness::on(DatabaseSelector::getConnection(substr($slsId, 0, 4)))
            ->whereIn('sls_id', [$slsId])
            // ->with(['sls', 'village'])
            ->get();

        return $this->successResponse($businesses, 'SLS retrieved successfully');
    }

    public function getBusinessByMultipleSls(Request $request)
    {
        // Validate the request
        $request->validate([
            'sls_ids' => 'required|array|min:1',
            'sls_ids.*' => 'required|integer|exists:sls,id'
        ]);

        $businesses = SlsBusiness::whereIn('sls_id', $request->input('sls_ids'))
            // ->with(['sls', 'village'])
            ->get();

        return $this->successResponse($businesses, 'SLS retrieved successfully');
    }

    public function updateNewPrelistSlsStatus(Request $request)
    {
        // Validate the request
        $request->validate([
            'sls_ids' => 'required|array|min:1',
            'sls_ids.*' => 'required|string',
            'downloaded' => 'required|boolean'
        ]);

        $slsIds = $request->input('sls_ids');
        $downloaded = $request->input('downloaded');
        try {
            SlsUpdatePrelist::whereIn('sls_id', $slsIds)
                ->update(['has_been_downloaded' => $downloaded]);

            return $this->successResponse('Sukses mengubah status download prelist baru');
        } catch (Exception $e) {
            return $this->errorResponse('Gagal mengubah status prelist: ' . $e->getMessage(), 500);
        }
    }
}
