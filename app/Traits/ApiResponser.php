<?php

namespace App\Traits;

trait ApiResponser
{
    protected function successResponse($data = null, $message = 'Success', $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'status_code' => $status
        ], $status);
    }

    protected function errorResponse($message = 'Error', $status = 500, $errors = null)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'status_code' => $status
        ], $status);
    }
}
