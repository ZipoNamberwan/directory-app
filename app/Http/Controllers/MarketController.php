<?php

namespace App\Http\Controllers;

use App\Imports\MarketBusinessImport;
use App\Jobs\MarketUploadNotificationJob;
use App\Models\Market;
use App\Models\MarketBusiness;
use App\Models\MarketUploadStatus;
use App\Models\Regency;
use App\Models\User;
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
        if ($user->hasRole('adminprov')) {
            $regencies = Regency::all();
        } else if ($user->hasRole('adminkab')) {
            $markets = Market::where('regency_id', $user->regency_id)->get();
        } else if ($user->hasRole('pml') || $user->hasRole('operator')) {
            $markets = Market::where('regency_id', $user->regency_id)->get();
        }

        return view('market.index', ['regencies' => $regencies, 'markets' => $markets]);
    }

    public function show()
    {

        $user = User::find(Auth::id());
        $markets = [];
        if ($user->hasRole('adminprov')) {
            $markets = Market::all();
        } else if ($user->hasRole('adminkab')) {
            $markets = Market::where('regency_id', $user->regency_id)->get();
        } else {
            $markets = Market::where('regency_id', $user->regency_id)->get();
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
            $customFileName = $user->firstname . '_' . $market->name . '_' . now()->format('Ymd_His') . Str::random(4) . '.' . $extension;

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
                ]);
            }

            return redirect('/pasar-upload')->with('success-upload', 'File telah diupload, cek status pada tabel di bawah!');
        }

        return redirect('/pasar-upload')->with('failed-upload', 'File gagal diupload, menyimpan log');
    }

    public function download(Request $request)
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
            $records = MarketBusiness::where('user_id', $user->id);
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
                    ->orWhereRaw('LOWER(owner) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
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

    public function getUploadData(Request $request)
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
        $markets = Market::where('regency_id', $regency)->get();

        return response()->json($markets);
    }
}
