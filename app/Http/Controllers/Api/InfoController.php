<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Info;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Http\Request;

class InfoController extends Controller
{
    use ApiResponser;

    public function index(Request $request)
    {
        $lastCheck = $request->last_check;
        $columns = ['id', 'title', 'subtitle', 'tags', 'type', 'is_published', 'published_at', 'created_at', 'updated_at'];
        if ($lastCheck == null) {
            $info = Info::select($columns)->orderBy('updated_at', 'desc')->get();
        } else {
            $info = Info::select($columns)->where('updated_at', '>', $lastCheck)->orderBy('updated_at', 'desc')->get();
        }

        $info = $info->map(function ($item) {
            $item->is_published = (int) $item->is_published;
            return $item;
        });
        return $this->successResponse($info, 'Info retrieved successfully');
    }

    public function show(string $id)
    {
        try {
            $info = Info::findOrFail($id);
            $info->is_published = (int) $info->is_published;
            return $this->successResponse($info, 'Info retrieved successfully');
        } catch (Exception $e) {
            return $this->errorResponse('Info not found', 404);
        }
    }

}
