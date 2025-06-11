<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketBusiness;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;

class TaggingController extends Controller
{
    use ApiResponser;

    public function getBusinessInBoundingBox(Request $request)
    {

        /**
         * This method expects a bounding box defined by:
         * - Southwest corner: (min_lat, min_lng)
         * - Northeast corner: (max_lat, max_lng)
         *
         * Example:
         *   SW = [-6.4, 106.7] ← bottom-left of the box
         *   NE = [-6.1, 107.1] ← top-right of the box
         *
         * The goal is to find all businesses whose coordinates
         * fall within this rectangular region.
         */

        // Example pasar atom
        // SW = [-7.243059984001882, 112.74173662745208] ← bottom-left of the box
        // NE = [-7.239133, 112.746125] ← top-right of the box

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

            // 🔍 Query businesses within the bounding box
            $businesses = MarketBusiness::whereBetween('latitude', [$minLat, $maxLat])
                ->whereBetween('longitude', [$minLng, $maxLng])
                ->get();

            return $this->successResponse($businesses, 'Businesses retrieved successfully');
    }
}
