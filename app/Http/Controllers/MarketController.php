<?php

namespace App\Http\Controllers;

use App\Imports\MarketBusinessImport;
use App\Jobs\MarketBusinessExportJob;
use App\Jobs\MarketUploadNotificationJob;
use App\Models\AssignmentStatus;
use App\Models\Market;
use App\Models\MarketBusiness;
use App\Models\MarketType;
use App\Models\MarketUploadStatus;
use App\Models\Organization;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class MarketController extends Controller
{
    public function index()
    {
        $user = User::find(Auth::id());
        $organizations = [];
        $markets = [];
        $users = [];
        $isAdmin = false;
        $marketTypes = [];

        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
            $isAdmin = true;
        } else if ($user->hasRole('adminkab')) {
            $markets = Market::where('organization_id', $user->organization_id)->get();
            $users = User::where('organization_id', $user->organization_id)->get();
            $isAdmin = true;
            $marketTypes = MarketType::all();
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $markets = $user->markets;
            $marketIds = $user->markets()->pluck('markets.id');

            $users = User::whereHas('markets', function ($query) use ($marketIds) {
                $query->whereIn('markets.id', $marketIds);
            })->get();
            $marketTypes = MarketType::all();
        }

        return view(
            'market.index',
            [
                'organizations' => $organizations,
                'markets' => $markets,
                'isAdmin' => $isAdmin,
                'userId' => $user->id,
                'users' => $users,
                'marketTypes' => $marketTypes,
            ]
        );
    }

    public function showUploadPage()
    {
        $user = User::find(Auth::id());
        $markets = [];
        $users = [];
        if ($user->hasRole('superadmin')) {
            $markets = Market::all();
        } else {
            $markets = $user->markets;
        }

        if ($user->hasRole('adminprov') || $user->hasRole('adminkab')) {
            $users = User::where('organization_id', $user->organization_id)->get();
        }

        $statuses = MarketUploadStatus::getStatusValues();

        return view('market.upload', ['markets' => $markets, 'statuses' => $statuses, 'users' => $users]);
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

        if (!$market) {
            return redirect('/pasar/upload')->with('failed-upload', 'Pasar tidak ditemukan. Silakan pilih pasar yang valid.');
        }

        $status = MarketUploadStatus::where('user_id', $user->id)
            ->where('market_id', $market->id)
            ->whereIn('status', ['start', 'loading'])
            ->first();

        if ($status != null) {
            return redirect('/pasar/upload')->with(
                'failed-upload',
                'Masih ada proses upload di pasar ' . $market->name . '. Tunggu hingga selesai.'
            );
        }

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
        return Storage::download('upload_swmaps/' . $status->filename);
    }

    public function getMarketData(Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if ($user->hasRole('adminprov')) {
            $records = MarketBusiness::query();
        } else if ($user->hasRole('adminkab')) {
            $records = MarketBusiness::whereHas('market', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        } else {
            $marketIds = $user->markets->pluck('id');
            $records = MarketBusiness::whereIn('market_id', $marketIds);
        }

        if ($request->organization && $request->organization !== 'all') {
            $records->whereHas('market', function ($query) use ($request) {
                $query->where('organization_id', $request->organization);
            });
        }

        if ($request->market && $request->market !== 'all') {
            $records->where('market_id', $request->market);
        }

        if ($request->user && $request->user !== 'all') {
            $records->where('user_id', $request->user);
        }

        if ($request->marketType && $request->marketType !== 'all') {
            $records->whereHas('market', function ($query) use ($request) {
                $query->where('market_type_id', $request->marketType);
            });
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
                $orderColumn = 'status';
            } else if ($request->order[0]['column'] == '2') {
                $orderColumn = 'address';
            } else if ($request->order[0]['column'] == '3') {
                $orderColumn = 'description';
            } else if ($request->order[0]['column'] == '4') {
                $orderColumn = 'sector';
            } else if ($request->order[0]['column'] == '5') {
                $orderColumn = 'note';
            }
        }

        $searchkeyword = null;
        if ($request->search != null) {
            $searchkeyword = $request->search['value'];
        }

        $data = $records->with(['user', 'market', 'regency', 'market.organization']);
        // $data = $records;

        if ($searchkeyword != null) {
            $data->where(function ($query) use ($searchkeyword) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(address) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(note) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereHas('user', function ($q) use ($searchkeyword) {
                        $q->whereRaw('LOWER(firstname) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
                    });
            });
        }
        $recordsFiltered = $data->count();

        if ($orderDir == 'asc') {
            $data = $data->orderBy($orderColumn);
        } else {
            $data = $data->orderByDesc($orderColumn);
        }

        if ($request->length != -1 && $request->length != null) {
            $data = $data->skip($request->start)
                ->take($request->length)->get();
        } else {
            $data = $data->get();
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
            $records = MarketUploadStatus::whereHas('user', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        } else {
            $records = MarketUploadStatus::where('user_id', $user->id);
        }

        if ($request->status && $request->status !== 'all') {
            $records->where('status', $request->status);
        }
        if ($request->user && $request->user !== 'all') {
            $records->where('user_id', $request->user);
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
                    ->orWhereRaw('LOWER(regency_name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                    ->orWhereRaw('LOWER(message) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
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
        $markets = Market::where('organization_id', $regency)->get();

        return response()->json($markets);
    }

    public function getMarketByFilter(Request $request)
    {
        $user = User::find(Auth::id());

        $organizationId = null;
        if ($user->hasRole('adminkab') || $user->hasRole('operator') || $user->hasRole('pml')) {
            $organizationId = $user->organization_id;
        } else if ('adminprov') {
            $organizationId = $request->organization;
        }

        if ($organizationId == null) {
            return response()->json([]);
        }

        $markets = Market::query();

        $markets->where('organization_id', $organizationId);

        if ($request->marketType && $request->marketType !== 'all') {
            $markets->whereHas('marketType', function ($query) use ($request) {
                $query->where('market_type_id', $request->marketType);
            });
        }

        return response()->json($markets->get());
    }

    public function getMarketTypes()
    {
        $marketTypes = MarketType::all();
        return response()->json($marketTypes);
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

            $role = $user->roles->first()->name;

            $organization = $request->organization;
            if ($user->hasRole('adminkab')) {
                $organization = $user->organization_id;
            }
            $market = $request->market;

            try {
                MarketBusinessExportJob::dispatch($organization, $market, $uuid, $role);
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

    public function showMarketDistributionPage()
    {
        $user = User::find(Auth::id());
        $organizations = [];
        $markets = [];
        if ($user->hasRole('adminprov')) {
            $organizations = Organization::all();
        } else if ($user->hasRole('adminkab')) {
            $markets = Market::where('organization_id', $user->organization_id)->get();
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $markets = $user->markets;
        }

        return view('market.distribution', [
            'organizations' => $organizations,
            'markets' => $markets,
        ]);
    }

    public function getMarketDistributionData(Request $request)
    {
        $user = User::find(Auth::id());

        $records = null;

        if (!$request->market) {
            return response()->json([]);
        }

        if ($user->hasRole('adminprov')) {
            $records = MarketBusiness::query();
        } else if ($user->hasRole('adminkab')) {
            $records = MarketBusiness::whereHas('market', function ($query) use ($user) {
                $query->where('organization_id', $user->organization_id);
            });
        } else {
            $marketIds = $user->markets->pluck('id');
            $records = MarketBusiness::whereIn('market_id', $marketIds);
        }

        if ($request->organization && $request->organization !== 'all') {
            $records->whereHas('market', function ($query) use ($request) {
                $query->where('organization_id', $request->organization);
            });
        }

        if ($request->market && $request->market !== 'all') {
            $records->where('market_id', $request->market);
        }

        $records = $records->select('id', 'name', 'longitude', 'latitude');

        return response()->json($records->get());
    }

    public function getMarketBusinessDetail($id)
    {
        $business = MarketBusiness::find($id);
        if ($business) {
            return response()->json($business);
        } else {
            return response()->json(['error' => 'Business not found'], 404);
        }
    }

    public function getMarketPolygon($id)
    {
        $path = "market_polygon/" . $id . ".geojson";
        if (!Storage::exists($path)) {
            abort(404);
        }

        $geojson = Storage::get($path);

        return Response::make($geojson, 200, [
            'Content-Type' => 'application/json',
        ]);
    }
}
