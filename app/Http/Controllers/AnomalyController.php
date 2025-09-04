<?php

namespace App\Http\Controllers;

use App\Models\AnomalyRepair;
use App\Models\AnomalyType;
use App\Models\Organization;
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
        $anomalyTypes = AnomalyType::all();
        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
        }
        return view('anomaly.index', ['organizations' => $organizations, 'anomalyTypes' => $anomalyTypes]);
    }

    public function getAnomalyListData(Request $request)
    {
        $user = User::find(Auth::id());

        $records = AnomalyRepair::query()
            ->join('anomaly_types', 'anomaly_repairs.anomaly_type_id', '=', 'anomaly_types.id')
            ->select(
                'anomaly_repairs.business_id',
                'anomaly_repairs.business_type',
                DB::raw("GROUP_CONCAT(DISTINCT anomaly_types.name ORDER BY anomaly_types.name SEPARATOR ', ') as anomaly_types"),
                DB::raw("GROUP_CONCAT(DISTINCT anomaly_repairs.status ORDER BY anomaly_repairs.status SEPARATOR ', ') as anomaly_statuses")
            )
            ->groupBy('anomaly_repairs.business_id', 'anomaly_repairs.business_type');

        // 🔍 filters by region (assuming business table has these columns)
        if ($request->regency && $request->regency !== 'all') {
            $records->where('regency_id', $request->regency);
        }
        if ($request->subdistrict && $request->subdistrict !== 'all') {
            $records->where('subdistrict_id', $request->subdistrict);
        }
        if ($request->village && $request->village !== 'all') {
            $records->where('village_id', $request->village);
        }
        if ($request->sls && $request->sls !== 'all') {
            $records->where('sls_id', $request->sls);
        }

        // 🔍 search (name, address, description, note)
        if ($request->keyword) {
            $search = strtolower($request->keyword);
            $records->where(function ($query) use ($search) {
                $query->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(address) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(note) LIKE ?', ["%{$search}%"]);
            });
        }

        // sorting
        $orderColumn = $request->get('sort_by', 'created_at');
        $orderDir = $request->get('sort_dir', 'desc');

        // ✅ get total BEFORE applying pagination
        $totalRecords = (clone $records)->count();

        // ✅ cap total count at 1000
        $total = min($totalRecords, 1000);

        // Progressive loading with page-based pagination
        $perPage = (int) $request->get('size', 20);
        $page = (int) $request->get('page', 1);

        // Calculate offset
        $offset = ($page - 1) * $perPage;

        // ✅ stop fetching more than 1000 rows
        if ($offset >= 1000) {
            return response()->json([
                "total_records" => $totalRecords,
                "last_page" => (int) ceil($total / $perPage),
                "data" => [],
            ]);
        }

        // Apply pagination
        $data = $records
            ->orderBy($orderColumn, $orderDir)
            ->offset($offset)
            ->limit(min($perPage, 1000 - $offset))
            ->get();

        return response()->json([
            "total_records" => $totalRecords,
            "last_page" => (int) ceil($total / $perPage),
            "data" => $data->toArray(),
        ]);
    }
}
