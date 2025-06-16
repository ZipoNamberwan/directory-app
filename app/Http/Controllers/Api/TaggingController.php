<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketBusiness;
use App\Models\Project;
use App\Models\SupplementBusiness;
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

        $project = Project::where('type', 'swmaps market')->first();
        // 🔍 Query businesses within the bounding box
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

        $supplementSwmapsBusinesses = SupplementBusiness::with(['project', 'user'])->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->get();

        $combinedBusiness = $marketBusinesses->merge($supplementSwmapsBusinesses);


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
            $project = Project::find($request->project['id']);
            if ($project == null) {
                $project = Project::create([
                    'id' => $request->project['id'],
                    'name' => $request->project['name'],
                    'description' => $request->project['description'],
                    'type' => 'kendedes mobile',
                    'user_id' => $request->user,
                ]);
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
            $project = Project::find($request->project['id']);
            if ($project == null) {
                $project = Project::create([
                    'id' => $request->project['id'],
                    'name' => $request->project['name'],
                    'description' => $request->project['description'],
                    'type' => 'kendedes mobile',
                    'user_id' => $request->user,
                ]);
            }

            $business = SupplementBusiness::updateOrCreate(
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

        foreach ($request->tags as $tagData) {
            try {
                $project = Project::find($tagData['project']['id']);
                if ($project == null) {
                    $project = Project::create([
                        'id' => $tagData['project']['id'],
                        'name' => $tagData['project']['name'],
                        'description' => $tagData['project']['description'],
                        'type' => 'kendedes mobile',
                        'user_id' => $tagData['user'],
                    ]);
                }
                $tag = SupplementBusiness::updateOrCreate(
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
                    ]
                );

                $uploadedIds[] = $tag->id;
            } catch (Exception $e) {
                continue;
            }
        }

        return $this->successResponse(
            data: ['uploaded_ids' => $uploadedIds],
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
            $deletedCount = SupplementBusiness::whereIn('id', $request->ids)->delete();
            return $this->successResponse(data: ['deleted_count' => $deletedCount, 'success' => true], message: 'Tagging berhasil dihapus', status: 200);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal menghapus tagging', 500);
        }
    }
}
