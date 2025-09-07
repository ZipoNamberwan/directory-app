<?php

namespace App\Http\Controllers;

use App\Models\AnomalyRepair;
use App\Models\AnomalyType;
use App\Models\Organization;
use App\Models\Regency;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AnomalyController extends Controller
{
    public function index()
    {
        $user = User::find(Auth::id());
        $organizations = [];
        $regencies = [];
        $subdistricts = [];
        $anomalyTypes = AnomalyType::all();
        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
            $regencies = Regency::all();
        } else {
            $regencies = Regency::where('id', $user->regency_id)->get();
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
        }
        return view('anomaly.index', [
            'organizations' => $organizations,
            'anomalyTypes' => $anomalyTypes,
            'regencies' => $regencies,
            'subdistricts' => $subdistricts
        ]);
    }

    public function getAnomalyListData(Request $request)
    {
        // Get pagination parameters
        $perPage = $request->get('size', 20);
        $page = $request->get('page', 1);
        $offset = ($page - 1) * $perPage;

        // Build the base query with joins for better performance
        $baseQuery = $this->buildOptimizedQuery($request);

        // Get paginated business IDs with a single optimized query
        $businessData = $this->getPaginatedBusinesses($baseQuery, $offset, $perPage);

        if (empty($businessData['business_ids'])) {
            return response()->json([
                "total_records" => 0,
                "last_page" => 1,
                "data" => [],
            ]);
        }

        // Get all anomalies for the paginated businesses in one query
        $anomaliesData = $this->getAnomaliesForBusinesses($businessData['business_ids'], $request);

        // Transform data efficiently
        $transformedData = $this->transformDataOptimized($businessData['businesses'], $anomaliesData);

        $totalRecord = $businessData['total'];

        return response()->json([
            "total_records" => $totalRecord,
            "last_page" => (int) ceil($totalRecord / $perPage),
            "data" => $transformedData,
        ]);
    }

    private function buildOptimizedQuery(Request $request): \Illuminate\Database\Query\Builder
    {
        $query = DB::table('anomaly_repairs as ar')
            ->leftJoin('supplement_business as sb', function ($join) {
                $join->on('ar.business_id', '=', 'sb.id')
                    ->where('ar.business_type', '=', 'App\\Models\\SupplementBusiness');
            })
            ->leftJoin('market_business as mb', function ($join) {
                $join->on('ar.business_id', '=', 'mb.id')
                    ->where('ar.business_type', '=', 'App\\Models\\MarketBusiness');
            })
            ->leftJoin('markets as m', 'mb.market_id', '=', 'm.id')
            ->leftJoin('anomaly_types as at', 'ar.anomaly_type_id', '=', 'at.id')

            // 👇 Join users for both sb.user_id and mb.user_id
            ->leftJoin('users as u_sb', 'sb.user_id', '=', 'u_sb.id')
            ->leftJoin('users as u_mb', 'mb.user_id', '=', 'u_mb.id')

            // 👇 Join organizations directly for supplement_business
            ->leftJoin('organizations as o_sb', 'sb.organization_id', '=', 'o_sb.id')

            // 👇 Join organizations through markets for market_business
            ->leftJoin('organizations as o_m', 'm.organization_id', '=', 'o_m.id');

        // Apply all filters directly in the join query for maximum performance
        $this->applyOptimizedFilters($query, $request);

        return $query;
    }

    private function getPaginatedBusinesses($baseQuery, int $offset, int $perPage): array
    {
        // Clone query for count (without select to avoid issues)
        $countQuery = clone $baseQuery;
        $total = $countQuery->distinct('ar.business_id')->count('ar.business_id');

        // Get paginated business data in one query using COALESCE to merge both business types
        $businesses = $baseQuery
            ->select([
                'ar.business_id',
                'ar.business_type',
                DB::raw('COALESCE(sb.name, mb.name) as name'),
                DB::raw('COALESCE(sb.status, mb.status) as status'),
                DB::raw('COALESCE(sb.address, mb.address) as address'),
                DB::raw('COALESCE(sb.description, mb.description) as description'),
                DB::raw('COALESCE(sb.sector, mb.sector) as sector'),
                DB::raw('COALESCE(sb.note, mb.note) as note'),
                DB::raw('COALESCE(sb.latitude, mb.latitude) as latitude'),
                DB::raw('COALESCE(sb.longitude, mb.longitude) as longitude'),
                DB::raw('COALESCE(sb.regency_id, mb.regency_id) as regency_id'),
                DB::raw('COALESCE(sb.subdistrict_id, mb.subdistrict_id) as subdistrict_id'),
                DB::raw('COALESCE(sb.village_id, mb.village_id) as village_id'),
                DB::raw('COALESCE(sb.sls_id, mb.sls_id) as sls_id'),
                DB::raw('COALESCE(sb.organization_id, m.organization_id) as organization_id'),
                // DB::raw('COALESCE(sb.owner, mb.owner) as owner')

                // 👇 User fields
                DB::raw('COALESCE(u_sb.firstname, u_mb.firstname) as firstname'),
                DB::raw('COALESCE(u_sb.email, u_mb.email) as email'),

                // 👇 Organization name from either path
                DB::raw('COALESCE(o_sb.name, o_m.name) as organization_name'),
                DB::raw('COALESCE(o_sb.long_code, o_m.long_code) as organization_long_code'),

                // 👇 Business owner (only from sb, will be NULL for mb)
                DB::raw('sb.owner as owner'),
            ])
            ->distinct('ar.business_id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(function ($item) {
                return (array) $item; // Convert stdClass to array
            })
            ->keyBy('business_id')
            ->toArray();

        return [
            'business_ids' => array_keys($businesses),
            'businesses' => $businesses,
            'total' => $total
        ];
    }

    private function getAnomaliesForBusinesses(array $businessIds, Request $request): array
    {
        $query = DB::table('anomaly_repairs as ar')
            ->leftJoin('anomaly_types as at', 'ar.anomaly_type_id', '=', 'at.id')
            ->whereIn('ar.business_id', $businessIds)
            ->select([
                'ar.id',
                'ar.business_id',
                'ar.business_type',
                'ar.status',
                'ar.anomaly_type_id',
                'ar.old_value',
                'ar.fixed_value',
                'ar.note',
                'ar.repaired_at',
                'ar.created_at',
                'ar.updated_at',
                'at.code as anomaly_type_code',
                'at.name as anomaly_type_name',
                'at.description as anomaly_type_description'
            ]);

        // Apply anomaly-specific filters only
        $this->applyAnomalyFilters($query, $request);

        // Order for consistent results
        $query->orderBy('ar.business_id')->orderBy('ar.created_at', 'desc');

        return $query->get()->groupBy('business_id')->toArray();
    }

    private function applyOptimizedFilters($query, Request $request): void
    {
        // Business filters - using COALESCE to check both business types
        if ($request->filled('regency')) {
            $query->where(function ($q) use ($request) {
                $q->where('sb.regency_id', $request->get('regency'))
                    ->orWhere('mb.regency_id', $request->get('regency'));
            });
        }

        if ($request->filled('subdistrict')) {
            $query->where(function ($q) use ($request) {
                $q->where('sb.subdistrict_id', $request->get('subdistrict'))
                    ->orWhere('mb.subdistrict_id', $request->get('subdistrict'));
            });
        }

        if ($request->filled('village')) {
            $query->where(function ($q) use ($request) {
                $q->where('sb.village_id', $request->get('village'))
                    ->orWhere('mb.village_id', $request->get('village'));
            });
        }

        if ($request->filled('sls')) {
            $query->where(function ($q) use ($request) {
                $q->where('sb.sls_id', $request->get('sls'))
                    ->orWhere('mb.sls_id', $request->get('sls'));
            });
        }

        if ($request->filled('organization')) {
            $query->where(function ($q) use ($request) {
                $q->where('sb.organization_id', $request->get('organization'))
                    ->orWhere('m.organization_id', $request->get('organization'));
            });
        }

        $mapBusinessType = [
            'market' => "App\Models\MarketBusiness",
            'supplement' => "App\Models\SupplementBusiness",
        ];
        if ($request->filled('businessType')) {
            $query->where('ar.business_type', $mapBusinessType[$request->get('businessType')]);
        }

        if ($request->filled('keyword')) {
            $keyword = '%' . $request->get('keyword') . '%';
            $query->where(function ($q) use ($keyword) {
                $q->where('sb.name', 'like', $keyword)
                    ->orWhere('mb.name', 'like', $keyword)
                    ->orWhere('sb.description', 'like', $keyword)
                    ->orWhere('mb.description', 'like', $keyword);
            });
        }

        // Anomaly filters
        $this->applyAnomalyFilters($query, $request);
    }

    private function applyAnomalyFilters($query, Request $request): void
    {
        if ($request->filled('anomalyStatus')) {
            $query->where('ar.status', $request->get('anomalyStatus'));
        }

        if ($request->filled('anomalyType')) {
            $query->where('ar.anomaly_type_id', $request->get('anomalyType'));
        }
    }

    private function transformDataOptimized(array $businesses, array $anomaliesData): array
    {
        $result = [];

        foreach ($businesses as $businessId => $business) {

            $anomalies = $anomaliesData[$businessId] ?? [];

            $result[] = [
                'id' => $business['business_id'],
                'type' => $business['business_type'],
                'business' => [
                    'id' => $business['business_id'],
                    'name' => $business['name'],
                    'description' => $business['description'],
                    'status' => $business['status'],
                    'address' => $business['address'],
                    'sector' => $business['sector'],
                    'note' => $business['note'],
                    'latitude' => $business['latitude'],
                    'longitude' => $business['longitude'],
                    'regency_id' => $business['regency_id'],
                    'subdistrict_id' => $business['subdistrict_id'],
                    'village_id' => $business['village_id'],
                    'sls_id' => $business['sls_id'],
                    'owner' => $business['owner'],
                ],
                // 👇 User info
                'user' => [
                    'firstname' => $business['firstname'],
                    'email'     => $business['email'],
                ],
                'organization' => [
                    'name' => $business['organization_name'],
                    'long_code' => $business['organization_long_code'],
                ],
                'anomalies' => array_map(function ($anomaly) {
                    return [
                        'id' => $anomaly->id,
                        'type' => $anomaly->anomaly_type_code,
                        'name' => $anomaly->anomaly_type_name,
                        'description' => $anomaly->anomaly_type_description,
                        'status' => $anomaly->status,
                        'old_value' => $anomaly->old_value,
                        'fixed_value' => $anomaly->fixed_value,
                        'note' => $anomaly->note,
                        'repaired_at' => $anomaly->repaired_at,
                        'created_at' => $anomaly->created_at,
                        'updated_at' => $anomaly->updated_at,
                    ];
                }, $anomalies)
            ];
        }

        return $result;
    }
}
