<?php

namespace App\Http\Controllers;

use App\Imports\MarketBusinessImport;
use App\Jobs\MarketBusinessExportJob;
use App\Jobs\MarketUploadNotificationJob;
use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\MarketBusiness;
use App\Models\MarketUploadStatus;
use App\Models\Regency;
use App\Models\ReportMarketBusinessMarket;
use App\Models\ReportMarketBusinessRegency;
use App\Models\ReportMarketBusinessUser;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketController extends Controller
{
    public function index()
    {
        $user = User::find(Auth::id());
        $regencies = [];
        $markets = [];
        $isAdmin = false;
        if ($user->hasRole('adminprov')) {
            $regencies = Regency::all();
            $isAdmin = true;
        } else if ($user->hasRole('adminkab')) {
            $markets = Market::where('regency_id', $user->regency_id)->get();
            $isAdmin = true;
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $markets = $user->markets;
        }

        return view(
            'market.index',
            [
                'regencies' => $regencies,
                'markets' => $markets,
                'isAdmin' => $isAdmin,
                'userId' => $user->id,
            ]
        );
    }

    public function show()
    {
        $user = User::find(Auth::id());
        $markets = [];
        if ($user->hasRole('superadmin')) {
            $markets = Market::all();
        } else {
            $markets = $user->markets;
        }

        return view('market.upload', ['markets' => $markets]);
    }

    public function upload(Request $request)
    {
        $validateArray = [
            'market' => 'required',
            'file' => 'required|file|mimes:xlsx,csv|max:2048',
        ];

        $request->validate($validateArray);
        $user = User::find(Auth::id());
        $market = Market::find($request->market);

        if ($request->hasFile('file') && $market != null) {
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $customFileName = $user->firstname . '_' . $market->name . '_' . now()->format('Ymd_His') . '_' . Str::random(4) . '.' . $extension;

            $file->storeAs('/upload_swmaps', $customFileName);

            $uuid = Str::uuid();
            $status = MarketUploadStatus::create([
                'id' => $uuid,
                'user_id' => $user->id,
                'market_id' => $market->id,
                'regency_id' => $market->regency_id,
                'filename' => $customFileName,
                'status' => 'start',

                'user_firstname' => $user->firstname,
                'market_name' => $market->name,
                'regency_name' => $market->regency->name
            ]);

            try {
                (new MarketBusinessImport($uuid))->queue('/upload_swmaps/' . $customFileName)->chain([
                    new MarketUploadNotificationJob($uuid),
                ]);
            } catch (Exception $e) {
                $status->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);
            }

            return redirect('/pasar/upload')->with('success-upload', 'File telah diupload, cek status pada tabel di bawah!');
        }

        return redirect('/pasar/upload')->with('failed-upload', 'File gagal diupload, menyimpan log');
    }

    public function downloadSwmapsExport(Request $request)
    {
        $status = MarketUploadStatus::find($request->id);
        return Storage::download('/upload_swmaps/' . $status->filename);
    }

    public function getMarketData(Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if ($user->hasRole('adminprov')) {
            $records = MarketBusiness::query();
        } else if ($user->hasRole('adminkab')) {
            $records = MarketBusiness::where('regency_id', $user->regency_id);
        } else {
            $marketIds = $user->markets->pluck('id');
            $records = MarketBusiness::whereIn('market_id', $marketIds);
        }

        if ($request->regency && $request->regency !== 'all') {
            $records->where('regency_id', $request->regency);
        }

        if ($request->market && $request->market !== 'all') {
            $records->where('market_id', $request->market);
        }

        $recordsTotal = $records->count();

        $orderColumn = 'created_at';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'name';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'owner';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'note';
            }
        }

        $searchkeyword = $request->search['value'];
        $data = $records->with(['user', 'market', 'regency']);
        // $data = $records;

        if ($searchkeyword != null) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(address) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(note) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
            });
        }
        $recordsFiltered = $data->count();

        if ($orderDir == 'asc') {
            $data = $data->orderBy($orderColumn);
        } else {
            $data = $data->orderByDesc($orderColumn);
        }

        if ($request->length != -1) {
            $data = $data->skip($request->start)
                ->take($request->length)->get();
        }

        $data = $data->values();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

    public function getUploadStatusData(Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if ($user->hasRole('adminprov')) {
            $records = MarketUploadStatus::query();
        } else if ($user->hasRole('adminkab')) {
            $records = MarketUploadStatus::where('regency_id', $user->regency_id);
        } else {
            $records = MarketUploadStatus::where('user_id', $user->id);
        }

        $recordsTotal = $records->count();

        $orderColumn = 'created_at';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '3') {
                $orderColumn = 'created_at';
            } else if ($request->order[0]['column'] == '0') {
                $orderColumn = 'market_name';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'user_firstname';
            } else if ($request->order[0]['column'] == '5') {
                $orderColumn = 'message';
            }
        }

        $searchkeyword = $request->search['value'];
        // $data = $records->with(['user', 'market']);
        $data = $records;

        if ($searchkeyword != null) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(market_name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(user_firstname) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(regency_id) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(regency_name) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
            });
        }
        $recordsFiltered = $data->count();

        if ($orderDir == 'asc') {
            $data = $data->orderBy($orderColumn);
        } else {
            $data = $data->orderByDesc($orderColumn);
        }

        if ($request->length != -1) {
            $data = $data->skip($request->start)
                ->take($request->length)->get();
        }

        $data = $data->values();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

    public function getMarketByRegency($regency)
    {
        $markets = [];
        if ($regency != '3500') {
            $markets = Market::where('regency_id', $regency)->get();
        } else {
            $markets = Market::where('regency_id', '3578')->get();
        }

        return response()->json($markets);
    }

    public function downloadUploadedData(Request $request)
    {

        $user = User::find(Auth::id());
        $uuid = Str::uuid();

        $status = AssignmentStatus::where('user_id', Auth::id())
            ->where('type', 'download-market-raw')
            ->whereIn('status', ['start', 'loading'])->first();

        if ($status == null) {
            $status = AssignmentStatus::create([
                'id' => $uuid,
                'status' => 'start',
                'user_id' => $user->id,
                'type' => 'download-market-raw',
            ]);

            $regency = $request->regency;
            if ($user->hasRole('adminkab')) {
                $regency = $user->regency_id;
            }
            $market = $request->market;

            try {
                MarketBusinessExportJob::dispatch($regency, $market, $uuid);
            } catch (Exception $e) {
                $status->update([
                    'status' => 'failed',
                    'message' => $e->getMessage(),
                ]);

                return redirect('/pasar')->with('failed-upload', 'Download gagal diproses, log sudah disimpan');
            }
            return redirect('/pasar')->with('success-upload', 'Download telah di proses, cek status pada tombol status');
        } else {
            return redirect('/pasar')->with('failed-upload', 'Download tidak diproses karena masih ada proses download yang belum selesai');
        }
    }

    public function getMarketBusinessDownloadStatus(Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if ($user->hasRole('adminprov')) {
            $records = AssignmentStatus::where('type', 'download-market-raw');
        } else if ($user->hasRole('adminkab') || $user->hasRole('pml') || $user->hasRole('operator')) {
            $records = AssignmentStatus::where(['user_id' => $user->id, 'type' => 'download-market-raw']);
        }

        $recordsTotal = $records->count();

        $orderColumn = 'created_at';
        $orderDir = 'desc';

        if (!empty($request->order)) {
            $columnIndex = $request->order[0]['column'];
            $direction = $request->order[0]['dir'] === 'asc' ? 'asc' : 'desc';

            // You can map column index from frontend to actual DB columns here
            switch ($columnIndex) {
                case '0':
                    $orderColumn = 'id';
                    break;
                case '1':
                    $orderColumn = 'status';
                    break;
                default:
                    $orderColumn = 'created_at';
            }

            $orderDir = $direction;
        }

        // Search
        $searchkeyword = $request->search['value'] ?? null;

        if (!empty($searchkeyword)) {
            $records->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(status) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(message) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
            });
        }

        $recordsFiltered = $records->count();

        // Pagination
        if ($request->length != -1) {
            $records->skip($request->start)
                ->take($request->length);
        }

        // Order
        $records->orderBy($orderColumn, $orderDir);

        $data = $records->get();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $data
        ]);
    }

    public function downloadMarketBusinessFile(Request $request)
    {
        $status = AssignmentStatus::find($request->id);
        return Storage::download('/market_business_raw/' . $status->id . '.csv');
    }

    public function dashboard()
    {
        $user = User::find(Auth::id());

        $reportByRegency = ReportMarketBusinessRegency::orderByDesc('date')
            ->limit(38)->get()->sortBy('regency_id')
            ->values();

        $chartReportByRegency = [];
        $totalBusiness = 0;
        $reportByUser = [];

        $reportByUser = ReportMarketBusinessUser::where(['date' => $reportByRegency[0]->date, 'regency_id' => $user->regency_id])->get();
        $reportByMarket = [];

        if ($user->hasRole('adminprov')) {
            $chartReportByRegency = ReportMarketBusinessRegency::selectRaw('date, SUM(uploaded) as uploaded')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get();

            $totalBusiness = $reportByRegency->sum('uploaded');

            $reportByMarket = ReportMarketBusinessMarket::where(['date' => $reportByRegency[0]->date, 'regency_id' => '3578'])->get();
        } else if ($user->hasRole('adminkab')) {
            $chartReportByRegency = ReportMarketBusinessRegency::where('regency_id', $user->regency_id)->orderByDesc('date')->limit(5)->get();

            $totalBusiness = $reportByRegency
                ->where('regency_id', $user->regency_id)->first()->uploaded;

            $reportByMarket = ReportMarketBusinessMarket::where(['date' => $reportByRegency[0]->date, 'regency_id' => $user->regency_id])->get();
        }

        $chartData = ['data' => ($chartReportByRegency->pluck('uploaded'))->reverse()->values(), 'dates' => ($chartReportByRegency->pluck('date'))->reverse()->values()];

        $updateDate = Carbon::parse($reportByRegency[0]->date)->translatedFormat('d F Y');
        $updateTime = Carbon::parse($reportByRegency[0]->created_at)->format('H:i');

        return view(
            'market.dashboard',
            [
                'reportByRegency' => $reportByRegency,
                'chartData' => $chartData,
                'updateDate' => $updateDate,
                'updateTime' => $updateTime,
                'totalBusiness' => $totalBusiness,
                'reportByUser' => $reportByUser,
                'reportByMarket' => $reportByMarket,
            ]
        );
    }

    public function deleteMarketBusiness($id)
    {
        $business = MarketBusiness::find($id);
        if ($business) {
            $business->delete();
            return redirect('/pasar')->with('success-upload', 'Usaha Telah Dihapus');
        } else {
            return redirect('/pasar')->with('failed-upload', 'Usaha gagal dihapus, menyimpan log');
        }
    }

    public function homeRedirect()
    {
        $user = User::find(Auth::id());

        if ($user->hasRole('adminprov') || $user->hasRole('adminkab')) {
            return redirect('/pasar-dashboard');
        } else {
            return redirect('/pasar');
        }
    }
}
