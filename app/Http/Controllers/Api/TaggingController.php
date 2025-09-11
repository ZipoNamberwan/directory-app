<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketBusiness;
use App\Models\Project;
use App\Models\SupplementBusiness;
use App\Models\SurveyBusiness;
use Illuminate\Http\Request;
use App\Traits\ApiResponser;
use Exception;

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


        // Query SWMAPS market businesses within the bounding box
        // We use the first project of type 'swmaps market' to attach to each business
        // This is to ensure that the project field is always present in the response
        $project = Project::where('type', 'swmaps market')->first();

        $marketBusinesses = MarketBusiness::with(['user'])->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
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

        // Query Supplement SWMAPS businesses within the bounding box
        $supplementSwmapsBusinesses = SupplementBusiness::with(['project', 'user'])
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->get();


        // Project field in mobile is mandatory, so we create a dummy project for survey businesses
        // This is a workaround to ensure the project field is always present
        $project = [
            'id' => 'survey',
            'name' => 'Survey Project',
            'type' => 'survey',
            'description' => null,
            'created_at' => '2024-06-28 10:15:30',
            'updated_at' => '2024-06-28 10:15:30',
        ];
        // Dummy user temporary only before all have migrated to new mobile version
        $dummyUser = [
            'id' => 'dummy-user',
            'firstname' => 'Survei BPS',
            'email' => 'dummy@example.com',
        ];
        // Query Survey businesses within the bounding box
        $surveyBusinesses = SurveyBusiness::with(['survey'])
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->get()
            ->map(function ($business) use ($project, $dummyUser) {
                $business->project = $project;
                $business->user = $dummyUser;
                return $business;
            });

        // Combine all businesses into a single collection
        $combinedBusiness = $marketBusinesses->merge($supplementSwmapsBusinesses)
            ->merge($surveyBusinesses);

        return $this->successResponse($combinedBusiness, 'Businesses retrieved successfully');
    }

    public function getBusinessByProject(String $projectId)
    {
        $businesses = SupplementBusiness::where('project_id', $projectId)->get();
        return $this->successResponse($businesses, 'Businesses retrieved successfully');
    }

    public function storeSupplementBusiness(Request $request)
    {
        $request->validate([
            'id' => 'required|uuid',
            'name' => 'required',
            'building' => 'required',
            'description' => 'required',
            'sector' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'project' => 'required',
            'user' => 'required|exists:users,id',
            'organization' => 'required|exists:organizations,id',
        ]);

        try {
            $project = Project::withTrashed()->find($request->project['id']);
            if ($project === null) {
                // Doesn't exist at all, safe to create
                $project = Project::create([
                    'id' => $request->project['id'],
                    'name' => $request->project['name'],
                    'description' => $request->project['description'],
                    'type' => 'kendedes mobile',
                    'user_id' => $request->user,
                ]);
            } elseif ($project->trashed()) {
                // It exists but is soft-deleted — restore and optionally update it
                $project->restore();
            }

            $business = SupplementBusiness::create([
                'id' => $request->id,
                'name' => $request->name,
                'owner' => $request->owner,
                'status' => $request->building,
                'address' => $request->address,
                'description' => $request->description,
                'sector' => $request->sector,
                'note' => $request->note,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'user_id' => $request->user,
                'project_id' => $request->project['id'],
                'organization_id' => $request->organization,
            ]);
            $business->load(['user', 'project']);

            return $this->successResponse(data: $business, status: 201);
        } catch (Exception $e) {
            return $this->errorResponse($e, 500);
        }
    }

    public function updateSupplementBusiness(Request $request, String $id)
    {
        $request->validate([
            'name' => 'required',
            'building' => 'required',
            'description' => 'required',
            'sector' => 'required',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'project' => 'required',
            'user' => 'required|exists:users,id',
            'organization' => 'required|exists:organizations,id',
        ]);

        try {
            // Check if business exists and is locked
            $existingBusiness = SupplementBusiness::withTrashed()->find($id);
            if ($existingBusiness && $existingBusiness->is_locked) {
                return $this->errorResponse('Usaha telah diedit oleh Admin, sehingga sudah tidak bisa diperbaiki', 423);
            }

            $project = Project::withTrashed()->find($request->project['id']);
            if ($project === null) {
                // Doesn't exist at all, safe to create
                $project = Project::create([
                    'id' => $request->project['id'],
                    'name' => $request->project['name'],
                    'description' => $request->project['description'],
                    'type' => 'kendedes mobile',
                    'user_id' => $request->user,
                ]);
            } elseif ($project->trashed()) {
                // It exists but is soft-deleted — restore and optionally update it
                $project->restore();
            }

            $business = SupplementBusiness::withTrashed()->updateOrCreate(
                ['id' => $id],
                [
                    'name' => $request->name,
                    'owner' => $request->owner,
                    'status' => $request->building,
                    'address' => $request->address,
                    'description' => $request->description,
                    'sector' => $request->sector,
                    'note' => $request->note,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,
                    'user_id' => $request->user,
                    'project_id' => $request->project['id'],
                    'organization_id' => $request->organization,
                    'deleted_at' => null
                ]
            );
            $business->load(['user', 'project']);

            return $this->successResponse(data: $business, status: 200);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui tagging', 500);
        }
    }

    public function deleteSupplementBusiness(String $id)
    {
        try {
            $business = SupplementBusiness::find($id);
            if (!$business) {
                return $this->successResponse(data: ['is_found' => false], message: 'Tagging tidak ditemukan', status: 200);
            }

            // Check if business is locked
            if ($business->is_locked) {
                return $this->errorResponse('Usaha telah diedit oleh Admin, sehingga sudah tidak bisa diperbaiki', 423);
            }

            $business->delete();
            return $this->successResponse(data: ['is_found' => true], message: 'Tagging berhasil dihapus', status: 200);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus tagging', 500);
        }
    }

    public function uploadMultipleTags(Request $request)
    {
        // create method to handle multiple tags upload, no need to validate, the return will be ids successfully uploaded
        $uploadedIds = [];
        $lockedIds = [];

        foreach ($request->tags as $tagData) {
            try {
                // Check if existing business is locked
                $existingBusiness = SupplementBusiness::withTrashed()->find($tagData['id']);
                if ($existingBusiness && $existingBusiness->is_locked) {
                    $lockedIds[] = $tagData['id'];
                    continue;
                }

                $project = Project::withTrashed()->find($tagData['project']['id']);

                if ($project === null) {
                    // ── 1. It doesn't exist at all → create it
                    $project = Project::create([
                        'id' => $tagData['project']['id'],
                        'name' => $tagData['project']['name'],
                        'description' => $tagData['project']['description'],
                        'type' => 'kendedes mobile',
                        'user_id' => $tagData['user'],
                    ]);
                } elseif ($project->trashed()) {
                    $project->restore();
                }
                $tag = SupplementBusiness::withTrashed()->updateOrCreate(
                    ['id' => $tagData['id']],
                    [
                        'name' => $tagData['name'],
                        'owner' => $tagData['owner'],
                        'status' => $tagData['building'],
                        'address' => $tagData['address'],
                        'description' => $tagData['description'],
                        'sector' => $tagData['sector'],
                        'note' => $tagData['note'],
                        'latitude' => $tagData['latitude'],
                        'longitude' => $tagData['longitude'],
                        'user_id' => $tagData['user'],
                        'project_id' => $tagData['project']['id'],
                        'organization_id' => $tagData['organization'],
                        'deleted_at' => null
                    ]
                );

                $uploadedIds[] = $tag->id;
            } catch (Exception $e) {
                continue;
            }
        }

        $responseData = ['uploaded_ids' => $uploadedIds];
        if (!empty($lockedIds)) {
            $responseData['locked_ids'] = $lockedIds;
        }

        return $this->successResponse(
            data: $responseData,
            message: 'Tagging berhasil diunggah',
            status: 201
        );
    }

    public function deleteMultipleTags(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'uuid',
        ]);

        try {
            // Check for locked businesses
            $lockedBusinesses = SupplementBusiness::whereIn('id', $request->ids)
                ->where('is_locked', true)
                ->pluck('id')
                ->toArray();

            if (!empty($lockedBusinesses)) {
                return $this->errorResponse(
                    'Beberapa usaha telah diedit oleh Admin, sehingga sudah tidak bisa diperbaiki. Update ke versi terbaru untuk lebih jelasnya.',
                    423,
                    ['locked_ids' => $lockedBusinesses]
                );
            }

            $deletedCount = SupplementBusiness::whereIn('id', $request->ids)->delete();
            return $this->successResponse(data: ['deleted_count' => $deletedCount, 'success' => true], message: 'Tagging berhasil dihapus', status: 200);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus tagging', 500);
        }
    }
}
