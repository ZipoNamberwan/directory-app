<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KbliStatistic;
use App\Models\Subdistrict;
use App\Models\Village;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    use ApiResponser;

    /**
     * Get statistics data by area long_code and type.
     *
     * @param string $type
     * @param string $longCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics($type, $longCode)
    {
        if (!in_array($type, ['subdistrict', 'village'])) {
            return $this->errorResponse('Invalid type specified. Use subdistrict or village.', 400);
        }

        if (strlen($longCode) < 4) {
            return $this->errorResponse('Invalid long_code.', 400);
        }

        // Find the area model (only active periods)
        $area = null;
        if ($type === 'subdistrict') {
            $area = Subdistrict::whereHas('period', function ($query) {
                $query->where('is_active', true);
            })->where('long_code', $longCode)->first();
        } else {
            $area = Village::whereHas('period', function ($query) {
                $query->where('is_active', true);
            })->where('long_code', $longCode)->first();
        }

        if (!$area) {
            return $this->errorResponse('Area not found.', 404);
        }

        // Get the statistics
        $statistics = KbliStatistic::where('area_id', $area->id)
            ->where('area_type', get_class($area))
            ->get();

        return $this->successResponse($statistics);
    }
}

