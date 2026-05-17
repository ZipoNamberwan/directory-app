<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponser;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    use ApiResponser;

    public function __construct(private FirebaseAuth $firebaseAuth) {}

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

    public function register(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'organization' => ['required'],
            'role' => ['required', 'string'],
        ]);

        $user = User::create([
            'username' => $request->email,
            'firstname' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('tx9cMEm87t3e'),
            'organization_id' => $request->organization,
            'is_kendedes_user' => true,
            'must_change_password' => false,
        ]);

        $user->assignRoleAllDatabase($request->role);
        $user->load(['organization', 'roles']);

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

    public function loginGoogle(Request $request)
    {
        $request->validate([
            'firebaseToken' => ['required'],
        ]);

        // Verify the token with Google and get user info
        try {
            $verifiedToken = $this->firebaseAuth->verifyIdToken($request->firebaseToken);
            $uid = $verifiedToken->claims()->get('sub');

            // Get full profile from Firebase
            $firebaseUser = $this->firebaseAuth->getUser($uid);

            // Get name from Google provider data if displayName is null
            $displayName = $firebaseUser->displayName;

            if (is_null($displayName) && !empty($firebaseUser->providerData)) {
                $displayName = $firebaseUser->providerData[0]->displayName;
            }
        } catch (\Throwable $e) {
            return $this->errorResponse('Token/Email Google tidak valid', 422);
        }

        // Find user by email
        $user = User::where('email', $firebaseUser->email)->with(['organization', 'roles'])->first();

        if (!$user) {
            return $this->successResponse([
                'is_user_exist' => false,
                'name' => $displayName ?? 'New User',
                'email' => $firebaseUser->email,
            ], 'User not found');
        }

        if (!$user->is_kendedes_user) {
            return $this->errorResponse('Akun ini tidak memiliki akses ke KDM. Silakan hubungi admin kab/kota untuk mengubah akses melalui Admin Kendedes Web', 422);
        }

        // Create new personal access token
        $token = $user->createToken('mobile-token')->plainTextToken;

        return $this->successResponse([
            'is_user_exist' => true,
            'user' => $user,
            'token' => $token
        ], 'Login successful');
    }
}
