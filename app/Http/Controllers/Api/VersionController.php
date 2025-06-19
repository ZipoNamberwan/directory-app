<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Version;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    use ApiResponser;
    public function shouldUpdate(Request $request)
    {
        //validate the request to ensure version and organization parameters are present
        $request->validate([
            'version' => 'required',
            'organization' => 'required|exists:organizations,id',
        ]);

        try {
            $latestVersion = Version::orderBy('version_code', 'desc')->first();
            if ($latestVersion->version_code > $request->version) {
                return $this->successResponse([
                    'should_update' => true,
                    'latest_version' => $latestVersion,
                ]);
            } else {
                return $this->successResponse([
                    'should_update' => false,
                    'latest_version' => $latestVersion,
                ]);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Error checking version: ' . $e->getMessage(), 500);
        }
    }
}
