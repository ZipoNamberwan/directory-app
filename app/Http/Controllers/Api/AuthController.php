<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Village;
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

        if (!$user->is_kendedes_user) {
            return $this->errorResponse('Akun ini tidak memiliki akses ke KDM. Silakan hubungi admin kab/kota untuk mengubah akses melalui Admin Kendedes Web', 422);
        }

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

    public function loginWilkerstat(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return $this->errorResponse('Email atau password keliru', 422);
        }

        // Only eager load wilkerstatSls WITHOUT nested village
        $user = User::where('email', $request->email)
            ->with(['organization', 'roles', 'wilkerstatSls'])
            ->first();

        if (!$user->is_kenarok_user) {
            return $this->errorResponse('Akun ini tidak memiliki akses ke Ken Arok. Silakan hubungi admin kab/kota untuk mengubah akses melalui Admin Kendedes Web', 422);
        }

        $token = $user->createToken('mobile-token')->plainTextToken;

        // Get unique village_ids from wilkerstatSls
        $villageIds = $user->wilkerstatSls->pluck('village_id')->unique()->values();

        // Fetch actual villages by ID
        $villages = Village::whereIn('id', $villageIds)->get();

        return $this->successResponse([
            'user' => $user,
            'villages' => $villages,
            'token' => $token
        ], 'Login successful');
    }
}
