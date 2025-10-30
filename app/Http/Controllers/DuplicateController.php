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
            // Limit to candidate records that belong to the user's organization (direct columns)
            $records->where(function ($q) use ($user) {
                $q->where('center_business_organization_id', $user->organization_id)
                  ->orWhere('nearby_business_organization_id', $user->organization_id);
            });
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            // Limit to candidate records that belong to the user's organization (direct columns)
            $records->where(function ($q) use ($user) {
                $q->where('center_business_organization_id', $user->organization_id)
                  ->orWhere('nearby_business_organization_id', $user->organization_id);
            });
        }

        // filters
        if ($request->organization) {
            // Use the denormalized organization ID columns on the duplicate_candidates table
            $records->where(function ($q) use ($request) {
                $q->where('center_business_organization_id', $request->organization)
                  ->orWhere('nearby_business_organization_id', $request->organization);
            });
        }

        if ($request->regency && $request->regency != 'all') {
            $records->where(function ($query) use ($request) {
                $query->whereHas('centerBusiness', function ($subQuery) use ($request) {
                    $subQuery->where('regency_id', $request->regency);
                })->orWhereHas('nearbyBusiness', function ($subQuery) use ($request) {
                    $subQuery->where('regency_id', $request->regency);
                });
            });
        }

        if ($request->subdistrict && $request->subdistrict != 'all') {
            $records->where(function ($query) use ($request) {
                $query->whereHas('centerBusiness', function ($subQuery) use ($request) {
                    $subQuery->where('subdistrict_id', $request->subdistrict);
                })->orWhereHas('nearbyBusiness', function ($subQuery) use ($request) {
                    $subQuery->where('subdistrict_id', $request->subdistrict);
                });
            });
        }

        if ($request->village && $request->village != 'all') {
            $records->where(function ($query) use ($request) {
                $query->whereHas('centerBusiness', function ($subQuery) use ($request) {
                    $subQuery->where('village_id', $request->village);
                })->orWhereHas('nearbyBusiness', function ($subQuery) use ($request) {
                    $subQuery->where('village_id', $request->village);
                });
            });
        }

        if ($request->sls && $request->sls != 'all') {
            $records->where(function ($query) use ($request) {
                $query->whereHas('centerBusiness', function ($subQuery) use ($request) {
                    $subQuery->where('sls_id', $request->sls);
                })->orWhereHas('nearbyBusiness', function ($subQuery) use ($request) {
                    $subQuery->where('sls_id', $request->sls);
                });
            });
        }

        if ($request->status && $request->status !== 'all') {
            if ($request->status === 'keepone') {
                $records->where('status', 'keep1')->orWhere('status', 'keep2');
            } else {
                $records->where('status', $request->status);
            }
        }

        if ($request->pairType && $request->pairType !== 'all') {
            if ($request->pairType === 'supplementall') {
                $records->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('center_business_type', 'App\Models\SupplementBusiness')
                            ->where('nearby_business_type', 'App\Models\SupplementBusiness');
                    });
                });
            } else if ($request->pairType === 'marketall') {
                $records->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('center_business_type', 'App\Models\MarketBusiness')
                            ->where('nearby_business_type', 'App\Models\MarketBusiness');
                    });
                });
            } else if ($request->pairType === 'supplementmarket') {
                $records->where(function ($query) {
                    $query->where(function ($q) {
                        $q->where('center_business_type', 'App\Models\SupplementBusiness')
                            ->where('nearby_business_type', 'App\Models\MarketBusiness');
                    })->orWhere(function ($q) {
                        $q->where('center_business_type', 'App\Models\MarketBusiness')
                            ->where('nearby_business_type', 'App\Models\SupplementBusiness');
                    });
                });
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
                $query->withTrashed()->with(['user']);
            },
            'nearbyBusiness' => function ($query) {
                $query->withTrashed()->with(['user']);
            },
            'lastConfirmedBy'
        ])->find($candidateId);

        // Load organization relationships based on business type
        $centerBusiness = $candidate->centerBusiness;
        if ($centerBusiness) {
            if ($centerBusiness instanceof \App\Models\SupplementBusiness) {
                $centerBusiness->load('organization');
            } elseif ($centerBusiness instanceof \App\Models\MarketBusiness) {
                $centerBusiness->load('market.organization');
                // Normalize organization for market businesses
                if (!$centerBusiness->organization && $centerBusiness->market) {
                    $centerBusiness->organization = $centerBusiness->market->organization;
                }
            }
        }

        $nearbyBusiness = $candidate->nearbyBusiness;
        if ($nearbyBusiness) {
            if ($nearbyBusiness instanceof \App\Models\SupplementBusiness) {
                $nearbyBusiness->load('organization');
            } elseif ($nearbyBusiness instanceof \App\Models\MarketBusiness) {
                $nearbyBusiness->load('market.organization');
                // Normalize organization for market businesses
                if (!$nearbyBusiness->organization && $nearbyBusiness->market) {
                    $nearbyBusiness->organization = $nearbyBusiness->market->organization;
                }
            }
        }

        // Add business type information directly to the business objects
        if ($centerBusiness) {
            $centerBusiness->type = get_class($centerBusiness);
        }

        if ($nearbyBusiness) {
            $nearbyBusiness->type = get_class($nearbyBusiness);
        }

        return response()->json([
            "center_business" => $centerBusiness,
            "nearby_business" => $nearbyBusiness,
        ]);
    }

    public function updateDuplicateCandidateStatus(Request $request, $candidateId)
    {
        $request->validate([
            'status' => 'required|in:keepall,keep1,keep2,deleteall',
        ]);

        $candidate = DuplicateCandidate::with([
            'centerBusiness' => function ($query) {
                $query->withTrashed()->with(['user']);
            },
            'nearbyBusiness' => function ($query) {
                $query->withTrashed()->with(['user']);
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
        } else if ($request->status === 'deleteall') {
            // Delete both businesses
            $centerBusiness = $candidate->centerBusiness;
            if ($centerBusiness && !$centerBusiness->trashed()) {
                $centerBusiness->is_locked = true;
                $centerBusiness->save();
                $centerBusiness->deleteWithSource('duplicate');
            }

            $nearbyBusiness = $candidate->nearbyBusiness;
            if ($nearbyBusiness && !$nearbyBusiness->trashed()) {
                $nearbyBusiness->is_locked = true;
                $nearbyBusiness->save();
                $nearbyBusiness->deleteWithSource('duplicate');
            }
        }

        // Refresh the candidate data with updated relationships
        $candidate->refresh();
        $candidate->load([
            'centerBusiness' => function ($query) {
                $query->withTrashed()->with(['user']);
            },
            'nearbyBusiness' => function ($query) {
                $query->withTrashed()->with(['user']);
            },
            'lastConfirmedBy'
        ]);

        // Load organization relationships based on business type after refresh
        if ($candidate->centerBusiness) {
            if ($candidate->centerBusiness instanceof \App\Models\SupplementBusiness) {
                $candidate->centerBusiness->load('organization');
            } elseif ($candidate->centerBusiness instanceof \App\Models\MarketBusiness) {
                $candidate->centerBusiness->load('market.organization');
                // Normalize organization for market businesses
                if (!$candidate->centerBusiness->organization && $candidate->centerBusiness->market) {
                    $candidate->centerBusiness->organization = $candidate->centerBusiness->market->organization;
                }
            }
        }

        if ($candidate->nearbyBusiness) {
            if ($candidate->nearbyBusiness instanceof \App\Models\SupplementBusiness) {
                $candidate->nearbyBusiness->load('organization');
            } elseif ($candidate->nearbyBusiness instanceof \App\Models\MarketBusiness) {
                $candidate->nearbyBusiness->load('market.organization');
                // Normalize organization for market businesses
                if (!$candidate->nearbyBusiness->organization && $candidate->nearbyBusiness->market) {
                    $candidate->nearbyBusiness->organization = $candidate->nearbyBusiness->market->organization;
                }
            }
        }

        // Add business type information directly to the business objects
        if ($centerBusiness) {
            $centerBusiness->type = get_class($centerBusiness);
        }

        if ($nearbyBusiness) {
            $nearbyBusiness->type = get_class($nearbyBusiness);
        }

        return response()->json([
            'message' => 'Status updated successfully',
            'candidate' => $candidate,
        ]);
    }
}
