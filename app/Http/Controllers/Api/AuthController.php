<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponser;

class AuthController extends Controller
{
    use ApiResponser;

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required',],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Email atau password keliru', 422);
        }

        $user = User::where('email', $request->email)->with(['organization', 'roles'])->first();

        // Create new personal access token
        $token = $user->createToken('mobile-token')->plainTextToken;

        return $this->successResponse([
            'user' => $user,
            'token' => $token
        ], 'Login successful');
    }

    public function logout(Request $request)
    {
        // Revoke current access token only
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Logout berhasil');
    }
}
