<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    use ApiResponser;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getProjectsByUser($user)
    {
        $projects = Project::where('user_id', $user)->get();
        return $this->successResponse($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeMobileProject(Request $request)
    {
        $request->validate([
            'id' => ['uuid', 'required'],
            'name' => ['required'],
            'user' => ['required', 'uuid', 'exists:users,id'],
        ]);

        try {
            $project = Project::create([
                'id' => $request->id,
                'name' => $request->name,
                'description' => $request->description,
                'type' => 'kendedes mobile',
                'user_id' => $request->user,
            ]);

            return $this->successResponse(data: $project, status: 201);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal membuat projek: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return $this->errorResponse('Projek tidak ditemukan', 404);
        }
        return $this->successResponse($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function updateMobileProject(Request $request, string $id)
    {
        $project = Project::find($id);
        if (!$project) {
            return $this->errorResponse('Projek tidak ditemukan', 404);
        }

        $request->validate([
            'name' => ['required'],
        ]);

        try {
            $arrayUpdate = [
                'name' => $request->name,
                'description' => $request->description,
            ];
            if ($request->has('user')) {
                $arrayUpdate['user_id'] = $request->user;
            }

            $project->update($arrayUpdate);

            return $this->successResponse($project);
        } catch (Exception $e) {
            return $this->errorResponse('Gagal memperbarui projek: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroyMobileProject(string $id)
    {
        try {
            $project = Project::find($id);
            if (!$project) {
                return $this->errorResponse('Projek tidak ditemukan', 404);
            }
            $project->delete();

            return $this->successResponse(null, 'Project deleted');
        } catch (Exception $e) {
            throw new Exception('Gagal menghapus projek: ' . $e->getMessage(), 500);
        }
    }
}
