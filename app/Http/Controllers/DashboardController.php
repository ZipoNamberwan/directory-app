<?php

namespace App\Http\Controllers;

use App\Jobs\ReportExportJob;
use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\MarketType;
use App\Models\Organization;
use App\Models\ReportMarketBusinessMarket;
use App\Models\ReportMarketBusinessRegency;
use App\Models\ReportMarketBusinessUser;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use League\Csv\Writer;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function showDashboardPage()
    {
        $user = User::find(Auth::id());

        $latestRow = ReportMarketBusinessRegency::orderByDesc('date')->first();
        $latestDate = $latestRow->date;

        $organizations = [];
        $chartReportByRegency = [];
        $numberOfDays = 10;
        $totalBusiness = 0;

        if ($user->hasRole('adminprov')) {
            $chartReportByRegency = ReportMarketBusinessRegency::selectRaw('date, SUM(uploaded) as uploaded')
                ->where('date', '>=', Carbon::now()->subDays($numberOfDays)->toDateString())
                ->groupBy('date')
                ->orderByDesc('date')
                ->get();

            $totalBusiness = $chartReportByRegency->first()->uploaded;

            $organizations = Organization::all();
        } else if ($user->hasRole('adminkab')) {
            $chartReportByRegency = ReportMarketBusinessRegency::selectRaw('date, SUM(uploaded) as uploaded')
                ->where('organization_id', $user->organization_id)
                ->where('date', '>=', Carbon::now()->subDays($numberOfDays)->toDateString())
                ->groupBy('date')
                ->orderByDesc('date')
                ->get();

            $totalBusiness = $chartReportByRegency->first()->uploaded;
        }

        $chartData = ['data' => ($chartReportByRegency->pluck('uploaded'))->reverse()->values(), 'dates' => ($chartReportByRegency->pluck('date'))->reverse()->values()];

        $updateDate = Carbon::parse($latestDate)->translatedFormat('d F Y');
        $updateTime = Carbon::parse($latestRow->created_at)->format('H:i');

        $marketTypes = MarketType::all();

        return view(
            'market.dashboard',
            [
                'chartData' => $chartData,
                'updateDate' => $updateDate,
                'updateTime' => $updateTime,
                'totalBusiness' => $totalBusiness,
                'marketTypes' => $marketTypes,
                'organizations' => $organizations,
                'date' => $latestDate
            ]
        );
    }

    public function getMarketReportData($date, Request $request)
    {
        $user = User::find(Auth::id());

        // Start base query
        $records = ReportMarketBusinessMarket::query()->where('date', $date);

        // Role-based filtering
        if ($user->hasRole('adminkab')) {
            $records->whereHas('market', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        }

        // Organization filter
        if ($request->organization && $request->organization !== 'all') {
            $records->whereHas('market', function ($query) use ($request) {
                $query->where('organization_id', $request->organization);
            });
        }

        // Market type filter
        if ($request->marketType && $request->marketType !== 'all') {
            $records->where('report_market_business_market.market_type_id', $request->marketType);
        }

        // Total count before filtering
        $recordsTotal = $records->count();

        // Join markets table to allow sorting by market name
        $data = $records->leftJoin('markets', 'report_market_business_market.market_id', '=', 'markets.id')
            ->select('report_market_business_market.*'); // keep base model fields

        // Search logic
        $searchkeyword = $request->search['value'];
        if (!empty($searchkeyword)) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereHas('market', function ($marketQuery) use ($searchkeyword) {
                    $marketQuery->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhere('village_id', 'LIKE', '%' . $searchkeyword . '%');
                });
            });
        }

        // Filtered count
        $recordsFiltered = $data->count();

        // Determine ordering
        $orderColumn = 'created_at'; // default
        $orderDir = 'desc';

        if (!empty($request->order)) {
            $columnIndex = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'] === 'asc' ? 'asc' : 'desc';

            // Map column index to DB columns
            switch ($columnIndex) {
                case 0:
                    $orderColumn = 'markets.name'; // market name
                    break;
                case 1:
                    $orderColumn = 'market_type_id';
                    break;
                case 2:
                    $orderColumn = 'markets.village_id';
                    break;
                case 3:
                    $orderColumn = 'target_category';
                    break;
                case 4:
                    $orderColumn = 'completion_status';
                    break;
                case 5:
                    $orderColumn = 'uploaded';
                    break;
                // add more as needed
                default:
                    $orderColumn = 'created_at';
            }
        }

        // Apply ordering
        $data->orderBy($orderColumn, $orderDir);

        // Pagination
        if ($request->length != -1) {
            $data = $data->skip($request->start)
                ->take($request->length);
        }

        // Get data and eager-load relations
        $result = $data->with(['market', 'market.regency', 'market.subdistrict', 'market.village', 'marketType'])->get();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $result->values()
        ]);
    }

    public function getUserReportData($date, Request $request)
    {
        $user = User::find(Auth::id());

        // Start base query
        $records = ReportMarketBusinessUser::query()->where('date', $date);

        // Role-based filtering
        if ($user->hasRole('adminkab')) {
            $records->where('report_market_business_user.organization_id', $user->organization_id);
        }

        // Organization filter
        if ($request->organization && $request->organization !== 'all') {
            $records->where('report_market_business_user.organization_id', $request->organization);
        }

        // Total count before filtering
        $recordsTotal = $records->count();

        // Join markets table to allow sorting by market name
        $data = $records->leftJoin('users', 'report_market_business_user.user_id', '=', 'users.id')
            ->select('report_market_business_user.*'); // keep base model fields

        // Search logic
        $searchkeyword = $request->search['value'];
        if (!empty($searchkeyword)) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereHas('user', function ($userQuery) use ($searchkeyword) {
                    $userQuery->whereRaw('LOWER(firstname) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
                });
            });
        }

        // Filtered count
        $recordsFiltered = $data->count();

        // Determine ordering
        $orderColumn = 'created_at'; // default
        $orderDir = 'desc';

        if (!empty($request->order)) {
            $columnIndex = $request->order[0]['column'];
            $orderDir = $request->order[0]['dir'] === 'asc' ? 'asc' : 'desc';

            // Map column index to DB columns
            switch ($columnIndex) {
                case 0:
                    $orderColumn = 'users.firstname'; // market name
                    break;
                case 1:
                    $orderColumn = 'uploaded';
                    break;
                // add more as needed
                default:
                    $orderColumn = 'created_at';
            }
        }

        // Apply ordering
        $data->orderBy($orderColumn, $orderDir);

        // Pagination
        if ($request->length != -1) {
            $data = $data->skip($request->start)
                ->take($request->length);
        }

        // Get data and eager-load relations
        $result = $data->with(['user', /* 'organization' */])->get();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $result->values()
        ]);
    }

    public function getRegencyReportData($date, Request $request)
    {
        if ($request->marketType === 'all') {
            // Aggregate for all market types
            $reportByRegency = ReportMarketBusinessRegency::where('date', $date)
                ->with('organization')
                ->orderByDesc('date')
                ->get()
                ->groupBy('organization_id')
                ->map(function ($group) {
                    return [
                        'organization_id' => $group->first()->organization_id,
                        'organization' => $group->first()->organization,
                        'total_market' => $group->sum('total_market'),
                        'target' => $group->sum('target'),
                        'non_target' => $group->sum('non_target'),
                        'not_start' => $group->sum('not_start'),
                        'on_going' => $group->sum('on_going'),
                        'done' => $group->sum('done'),
                        'market_have_business' => $group->sum('market_have_business'),
                        'uploaded' => $group->sum('uploaded'),
                    ];
                })
                ->sortBy('organization_id')
                ->values();
        } else {
            // Specific market type
            $reportByRegency = ReportMarketBusinessRegency::where('market_type_id', $request->marketType)
                ->where('date', $date)
                ->with('organization')
                ->orderByDesc('date')
                ->limit(39)
                ->get()
                ->sortBy('organization_id')
                ->values();
        }

        return $reportByRegency;
    }

    public function showDownloadReportPage()
    {
        $marketTypes = MarketType::all();
        return view('market.download', ['marketTypes' => $marketTypes]);
    }

    public function downloadReport(Request $request)
    {
        $marketTypeIds = MarketType::pluck('id')->map(fn($id) => (string) $id)->toArray();
        $marketTypeIds[] = 'all'; // Add 'all' to the allowed values

        $validateArray = [
            'report' => 'required|in:regency,user,market',
            'marketType' => [
                'required_if:report,regency',
                'nullable',
                Rule::in($marketTypeIds),
            ],
        ];

        $validator = Validator::make($request->all(), $validateArray);
        $validator->validate();

        $type = 'dashboard-' . $request->report;
        $date = ReportMarketBusinessRegency::orderByDesc('date')->first()->date;

        $user = User::find(Auth::id());
        $uuid = Str::uuid();

        $status = AssignmentStatus::where('user_id', Auth::id())
            ->where('type', $type)
            ->whereIn('status', ['start', 'loading'])->first();

        if ($status == null) {
            $status = AssignmentStatus::create([
                'id' => $uuid,
                'status' => 'start',
                'user_id' => $user->id,
                'type' => $type,
            ]);

            try {
                ReportExportJob::dispatch($uuid, $date, $request->report, $request->marketType);
            } catch (Exception $e) {
                $status->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);

                return redirect('/pasar-dashboard/download')->with('error-delete', 'Download gagal diproses, log sudah disimpan');
            }
            return redirect('/pasar-dashboard/download')->with('success-edit', 'Download telah di proses, cek status pada tombol status');
        } else {
            return redirect('/pasar-dashboard/download')->with('error-delete', 'Download tidak diproses karena masih ada proses download yang belum selesai');
        }
    }
}
