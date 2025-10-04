<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionLeresPak;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VersionController extends Controller
{
    use ApiResponser;
    public function shouldUpdateKendedes(Request $request)
    {
        //validate the request to ensure version and organization parameters are present
        $request->validate([
            'version' => 'required',
            'organization' => 'required|exists:organizations,id',
        ]);

        try {
            $latestVersion = Version::orderBy('version_code', 'desc')->first();

            // Check if updates are allowed for this organization
            if (!$this->isUpdateAllowedForOrganization($request->organization, 'kendedes')) {
                return $this->successResponse([
                    'should_update' => false,
                    'latest_version' => $latestVersion,
                    'message' => 'Updates are not currently available for your organization',
                ]);
            }

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

    public function shouldUpdateLeresPak(Request $request)
    {
        //validate the request to ensure version and organization parameters are present
        $request->validate([
            'version' => 'required',
            'organization' => 'required|exists:organizations,id',
        ]);

        try {
            $latestVersion = VersionLeresPak::orderBy('version_code', 'desc')->first();
            $user = User::find(Auth::user()->id);
            $sls = $user->wilkerstatSls()->with('updatePrelist')->get();

            // Check if updates are allowed for this organization
            if (!$this->isUpdateAllowedForOrganization($request->organization, 'leres_pak')) {
                return $this->successResponse([
                    'should_update' => false,
                    'latest_version' => $latestVersion,
                    'assignments' => $sls,
                    'message' => 'Updates are not currently available for your organization',
                ]);
            }

            if ($latestVersion->version_code > $request->version) {
                return $this->successResponse([
                    'should_update' => true,
                    'latest_version' => $latestVersion,
                    'assignments' => $sls
                ]);
            } else {
                return $this->successResponse([
                    'should_update' => false,
                    'latest_version' => $latestVersion,
                    'assignments' => $sls
                ]);
            }
        } catch (\Exception $e) {
            return $this->errorResponse('Error checking version: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if updates are allowed for a specific organization and app type
     * 
     * @param int $organizationId
     * @param string $appType (kendedes or leres_pak)
     * @return bool
     */
    private function isUpdateAllowedForOrganization($organizationId, $appType)
    {
        // Define allowed organizations directly here
        $allowedOrganizations = [
            // Global setting - if set to ['all'], all organizations can update
            'general' => ['all'], // or specify organization IDs: [1, 2, 3]

            // App-specific settings (overrides general setting)
            'kendedes' => ['all'], // or specify organization IDs: [1, 2, 3]
            'leres_pak' => ['all'], // or specify organization IDs: [4, 5, 6]
        ];

        // If 'all' is in the configuration, allow updates for all organizations
        if (in_array('all', $allowedOrganizations)) {
            return true;
        }

        // Check if this specific organization is allowed for this app type
        if (isset($allowedOrganizations[$appType])) {
            // If it's set to 'all' for this app type
            if (in_array('all', $allowedOrganizations[$appType])) {
                return true;
            }

            // Check if the organization ID is in the allowed list for this app type
            return in_array($organizationId, $allowedOrganizations[$appType]);
        }

        // If no specific configuration for this app type, check general allowed organizations
        if (isset($allowedOrganizations['general'])) {
            if (in_array('all', $allowedOrganizations['general'])) {
                return true;
            }
            return in_array($organizationId, $allowedOrganizations['general']);
        }

        // Default: allow updates for all organizations if no configuration is set
        return true;
    }
}
