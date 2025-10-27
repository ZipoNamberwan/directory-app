<?php

namespace App\Http\Controllers;

use App\Models\DuplicateCandidate;
use App\Models\Organization;
use App\Models\Regency;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DuplicateController extends Controller
{
    public function showDuplicatePage()
    {
        $user = User::find(Auth::id());
        $organizations = [];
        $regencies = [];
        $subdistricts = [];

        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
            $regencies = Regency::all();
        } else if ($user->hasRole('adminkab')) {
            $regencies = Regency::where('id', $user->regency_id)->get();
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $regencies = Regency::where('id', $user->regency_id)->get();
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
        }

        return view('duplicate.index', [
            'organizations' => $organizations,
            'regencies' => $regencies,
            'subdistricts' => $subdistricts,
            'color' => 'secondary'
        ]);
    }

    public function getDuplicateCandidateData(Request $request)
    {
        $user = User::find(Auth::id());
        // base query
        $records = DuplicateCandidate::query();

        if ($user->hasRole('adminprov')) {
            // no additional filters
        } else if ($user->hasRole('adminkab')) {
            $records->whereHas('centerBusiness', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            })->orWhereHas('nearbyBusiness', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $records->whereHas('centerBusiness', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            })->orWhereHas('nearbyBusiness', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        }

        // filters
        if ($request->organization) {
            $records->whereHas('centerBusiness', function ($query) use ($request) {
                $query->where('organization_id', $request->organization);
            })->orWhereHas('nearbyBusiness', function ($query) use ($request) {
                $query->where('organization_id', $request->organization);
            });
        }

        if ($request->regency && $request->regency != 'all') {
            $records->whereHas('centerBusiness', function ($query) use ($request) {
                $query->where('regency_id', $request->regency);
            })->orWhereHas('nearbyBusiness', function ($query) use ($request) {
                $query->where('regency_id', $request->regency);
            });
        }

        if ($request->subdistrict && $request->subdistrict != 'all') {
            $records->whereHas('centerBusiness', function ($query) use ($request) {
                $query->where('subdistrict_id', $request->subdistrict);
            })->orWhereHas('nearbyBusiness', function ($query) use ($request) {
                $query->where('subdistrict_id', $request->subdistrict);
            });
        }

        if ($request->village && $request->village != 'all') {
            $records->whereHas('centerBusiness', function ($query) use ($request) {
                $query->where('village_id', $request->village);
            })->orWhereHas('nearbyBusiness', function ($query) use ($request) {
                $query->where('village_id', $request->village);
            });
        }

        if ($request->sls && $request->sls != 'all') {
            $records->whereHas('centerBusiness', function ($query) use ($request) {
                $query->where('sls_id', $request->sls);
            })->orWhereHas('nearbyBusiness', function ($query) use ($request) {
                $query->where('sls_id', $request->sls);
            });
        }

        if ($request->status && $request->status !== 'all') {
            if ($request->status === 'keepone') {
                $records->where('status', 'keep1')->orWhere('status', 'keep2');
            } else {
                $records->where('status', $request->status);
            }
        }

        // search
        if ($request->keyword) {
            $search = strtolower($request->keyword);
            $records->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(center_business_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(nearby_business_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(center_business_owner) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(nearby_business_owner) LIKE ?', ["%{$search}%"]);
            });
        }

        // sorting
        $defaultSortColumn = 'created_at';
        $orderColumn = $request->get('sort_by', $defaultSortColumn);
        $orderDir = $request->get('sort_dir', 'desc');

        // ✅ get total BEFORE applying pagination
        $totalRecords = (clone $records)->count();

        // ✅ cap total count at 1000
        $total = min($totalRecords, 1000);

        // Progressive loading with page-based pagination
        $perPage = (int) $request->get('size', 20); // Match your paginationSize
        $page = (int) $request->get('page', 1);

        // Calculate offset for the current page
        $offset = ($page - 1) * $perPage;

        // ✅ stop fetching more than 1000 rows
        if ($offset >= 1000) {
            return response()->json([
                "total_records" => $totalRecords,
                "last_page" => (int) ceil($total / $perPage),
                "data" => [],
            ]);
        }

        // Apply pagination with offset and limit
        $data = $records
            ->with(['lastConfirmedBy'])
            ->orderBy($orderColumn, $orderDir)
            ->offset($offset)
            ->limit(min($perPage, 1000 - $offset)) // Don't exceed the 1000 cap
            ->get();

        return response()->json([
            "total_records" => $totalRecords,
            "last_page" => (int) ceil($total / $perPage),
            "data" => $data->toArray(),
        ]);
    }

    public function getPairCandidateBusinessDetail($candidateId)
    {
        $candidate = DuplicateCandidate::with([
            'centerBusiness' => function ($query) {
                $query->withTrashed()->with(['user', 'organization']);
            },
            'nearbyBusiness' => function ($query) {
                $query->withTrashed()->with(['user', 'organization']);
            },
            'lastConfirmedBy'
        ])->find($candidateId);

        return response()->json([
            "center_business" => $candidate->centerBusiness,
            "nearby_business" => $candidate->nearbyBusiness,
        ]);
    }

    public function updateDuplicateCandidateStatus(Request $request, $candidateId)
    {
        $request->validate([
            'status' => 'required|in:keepall,keep1,keep2',
        ]);

        $candidate = DuplicateCandidate::with([
            'centerBusiness' => function ($query) {
                $query->withTrashed();
            },
            'nearbyBusiness' => function ($query) {
                $query->withTrashed();
            },
            'lastConfirmedBy'
        ])->find($candidateId);

        if (!$candidate) {
            return response()->json(['message' => 'Candidate not found'], 404);
        }

        $candidate->status = $request->status;
        $candidate->last_confirmed_by = Auth::id();
        $candidate->save();

        if ($request->status === 'keep2') {
            // Delete center business
            $centerBusiness = $candidate->centerBusiness;
            if ($centerBusiness && !$centerBusiness->trashed()) {
                $centerBusiness->is_locked = true;
                $centerBusiness->save();
                $centerBusiness->deleteWithSource('duplicate');
            }

            // Ensure nearby business is not deleted (restore if it was deleted)
            $nearbyBusiness = $candidate->nearbyBusiness;
            if ($nearbyBusiness && $nearbyBusiness->trashed()) {
                $nearbyBusiness->deleted_at = null;
                $nearbyBusiness->is_locked = true;
                $nearbyBusiness->save();
            }
        } else if ($request->status === 'keep1') {
            // Delete nearby business
            $nearbyBusiness = $candidate->nearbyBusiness;
            if ($nearbyBusiness && !$nearbyBusiness->trashed()) {
                $nearbyBusiness->is_locked = true;
                $nearbyBusiness->save();
                $nearbyBusiness->deleteWithSource('duplicate');
            }

            // Ensure center business is not deleted (restore if it was deleted)
            $centerBusiness = $candidate->centerBusiness;
            if ($centerBusiness && $centerBusiness->trashed()) {
                $centerBusiness->deleted_at = null;
                $centerBusiness->is_locked = true;
                $centerBusiness->save();
            }
        } else if ($request->status === 'keepall') {
            // Restore both businesses if they were deleted
            $centerBusiness = $candidate->centerBusiness;
            if ($centerBusiness && $centerBusiness->trashed()) {
                $centerBusiness->deleted_at = null;
                $centerBusiness->is_locked = true;
                $centerBusiness->save();
            }

            $nearbyBusiness = $candidate->nearbyBusiness;
            if ($nearbyBusiness && $nearbyBusiness->trashed()) {
                $nearbyBusiness->deleted_at = null;
                $nearbyBusiness->is_locked = true;
                $nearbyBusiness->save();
            }
        }

        // Refresh the candidate data with updated relationships
        $candidate->refresh();
        $candidate->load([
            'centerBusiness' => function ($query) {
                $query->withTrashed()->with(['user', 'organization']);
            },
            'nearbyBusiness' => function ($query) {
                $query->withTrashed()->with(['user', 'organization']);
            },
            'lastConfirmedBy'
        ]);

        return response()->json([
            'message' => 'Status updated successfully',
            'candidate' => $candidate,
        ]);
    }
}
