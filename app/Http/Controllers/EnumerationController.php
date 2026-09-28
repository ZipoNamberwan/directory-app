<?php

namespace App\Http\Controllers;

use App\Models\Regency;
use App\Models\Sls;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\UserSlsCensus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class EnumerationController extends Controller
{
    public function showAllocationPage()
    {
        $user = User::find(Auth::id());
        $regencies = [];
        $subdistricts = [];

        if ($user->hasRole('adminprov')) {
            $regencies = Regency::orderBy('long_code')->get();
        } else if ($user->organization_id) {
            $regencies = Regency::where('long_code', $user->organization_id)->get();
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->orderBy('long_code')->get();
        }

        return view('enumeration.allocation', [
            'regencies' => $regencies,
            'subdistricts' => $subdistricts,
            'color' => 'success',
        ]);
    }

    public function getAllocationData(Request $request)
    {
        $user = User::find(Auth::id());

        $records = UserSlsCensus::query();

        if ($user->hasRole('adminkab')) {
            $records->whereHas('user', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        } else if (!$user->hasRole('adminprov')) {
            $records->whereHas('user', function ($query) use ($user) {
                $query->where('regency_id', $user->regency_id);
            });
        }

        if ($request->regency && $request->regency !== 'all') {
            $records->whereHas('sls.village.subdistrict', function ($query) use ($request) {
                $query->where('regency_id', $request->regency);
            });
        }
        if ($request->subdistrict && $request->subdistrict !== 'all') {
            $records->whereHas('sls.village', function ($query) use ($request) {
                $query->where('subdistrict_id', $request->subdistrict);
            });
        }
        if ($request->village && $request->village !== 'all') {
            $records->whereHas('sls', function ($query) use ($request) {
                $query->where('village_id', $request->village);
            });
        }
        if ($request->sls && $request->sls !== 'all') {
            $records->where('sls_id', $request->sls);
        }

        if ($request->keyword) {
            $keyword = strtolower($request->keyword);
            $records->whereHas('user', function ($query) use ($keyword) {
                $query->where(function ($query) use ($keyword) {
                    $query->whereRaw('LOWER(firstname) LIKE ?', ["%{$keyword}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$keyword}%"]);
                });
            });
        }

        $totalRecords = (clone $records)->count();

        $perPage = (int) $request->get('size', 20);
        $page = (int) $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        $data = $records
            ->with(['user', 'sls.village.subdistrict.regency'])
            ->offset($offset)
            ->limit($perPage)
            ->get();

        return response()->json([
            'total_records' => $totalRecords,
            'last_page' => $perPage > 0 ? (int) ceil($totalRecords / $perPage) : 1,
            'data' => $data,
        ]);
    }

    public function storeManualAllocation(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'sls_long_code' => 'required|string',
        ]);

        $user = User::find(Auth::id());

        $targetUser = User::whereRaw('LOWER(email) = ?', [strtolower($request->email)])->first();
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'message' => 'Petugas dengan email tersebut tidak ditemukan',
            ], 404);
        }

        $sls = Sls::with('village.subdistrict')->where('long_code', trim($request->sls_long_code))->first();
        if (!$sls) {
            return response()->json([
                'success' => false,
                'message' => 'SLS dengan kode tersebut tidak ditemukan',
            ], 404);
        }

        if ($user->hasRole('adminkab')) {
            $regency = Regency::where('long_code', $user->organization_id)->first();
            if (!$regency || $sls->village->subdistrict->regency_id !== $regency->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'SLS berada di luar wilayah kabupaten Anda',
                ], 403);
            }
        }

        $allocation = UserSlsCensus::where('user_id', $targetUser->id)->where('sls_id', $sls->id)->first();
        if ($allocation) {
            return response()->json([
                'success' => true,
                'message' => 'Alokasi sudah ada sebelumnya',
                'data' => $allocation->load(['user', 'sls.village.subdistrict.regency']),
            ]);
        }

        $allocation = UserSlsCensus::create([
            'id' => Str::uuid(),
            'user_id' => $targetUser->id,
            'sls_id' => $sls->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Alokasi berhasil ditambahkan',
            'data' => $allocation->load(['user', 'sls.village.subdistrict.regency']),
        ]);
    }
}
