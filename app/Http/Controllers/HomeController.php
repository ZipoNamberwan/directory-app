<?php

namespace App\Http\Controllers;

use App\Models\NonSlsBusiness;
use App\Models\SlsBusiness;
use App\Models\Sls;
use App\Models\Status;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    // }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {

        $user = User::find(Auth::id());

        if ($user->hasRole('pcl')) {
            $businessBase = SlsBusiness::where(['pcl_id' => Auth::id()]);
            $total = (clone $businessBase)->count();
            $not_done = (clone $businessBase)->where(['status_id' => 1])->count();
            $active = (clone $businessBase)->where(['status_id' => 2])->count();
            $not_active = (clone $businessBase)->where(['status_id' => 3])->count();
            $new = (clone $businessBase)->where(['status_id' => 4])->count();
            $statuses = Status::all()->sortBy('order');
            $subdistricts = Subdistrict::where('regency_id', User::find(Auth::id())->regency_id)->get();

            return view('pcl.index', [
                'total' => $total,
                'not_done' => $not_done,
                'active' => $active,
                'not_active' => $not_active,
                'new' => $new,
                'statuses' => $statuses,
                'subdistricts' => $subdistricts
            ]);
        } else if ($user->hasRole('adminkab')) {
            $businessBase = SlsBusiness::where(['regency_id' => User::find(Auth::id())->regency_id]);
            $total = (clone $businessBase)->count();
            $not_done = (clone $businessBase)->where(['status_id' => 1])->count();
            $active = (clone $businessBase)->where(['status_id' => 2])->count();
            $not_active = (clone $businessBase)->where(['status_id' => 3])->count();
            $new = (clone $businessBase)->where(['status_id' => 4])->count();
            $statuses = Status::all()->sortBy('order');
            $subdistricts = Subdistrict::where('regency_id', User::find(Auth::id())->regency_id)->get();

            return view('adminkab.index', [
                'total' => $total,
                'not_done' => $not_done,
                'active' => $active,
                'not_active' => $not_active,
                'new' => $new,
                'statuses' => $statuses,
                'subdistricts' => $subdistricts
            ]);
        }

        return view('pages.dashboard');
    }

    public function getVillage($subdistrict_id)
    {
        $user = User::find(Auth::id());

        $village = [];

        if ($user->hasRole('pcl')) {
            $village = Village::whereIn(
                'id',
                $user->slsBusiness()->select('village_id')->where('village_id', 'like', "{$subdistrict_id}%")->distinct()->pluck('village_id')
            )->get();
        } else if ($user->hasRole('adminkab') || $user->hasRole('pml')) {
            $village = Village::where('subdistrict_id', $subdistrict_id)->get();
        }

        return response()->json($village);
    }
    public function getSls($village_id)
    {
        $user = User::find(Auth::id());

        $sls = [];
        if ($user->hasRole('pcl')) {
            $sls = Sls::whereIn(
                'id',
                $user->slsBusiness()->select('sls_id')->where('sls_id', 'like', "{$village_id}%")->distinct()->pluck('sls_id')
            )->get();
        } else if ($user->hasRole('adminkab')) {
            $sls = Sls::where('village_id', $village_id)->get();
        }

        return response()->json($sls);
    }
    public function getSlsDirectory($id_sls)
    {
        $user = User::find(Auth::id());
        $business = [];

        if ($user->hasRole('pcl')) {
            $business = $user->slsBusiness()->where('sls_id', '=', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();
        } else if ($user->hasRole('adminkab')) {
            $business = SlsBusiness::where('sls_id', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();;
        }
        return response()->json($business);
    }

    public function getSlsDirectoryTables(Request $request)
    {
        $user = User::find(Auth::id());
        $records = null;

        if ($user->hasRole('pcl')) {
            $records = $user->slsBusiness();
        } elseif ($user->hasRole('adminkab')) {
            $records = SlsBusiness::where('regency_id', $user->regency_id);
        }

        // Apply filters
        if ($request->status && $request->status !== 'all') {
            $records->where('status_id', $request->status);
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

        if ($request->assignment !== null) {
            $records->where('pcl_id', $request->assignment == '1' ? '!=' : '=', null);
        }

        $recordsTotal = $records->count();

        $orderColumn = 'sls_id';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
                $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'sls_id';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'name';
            } else if ($request->order[0]['column'] == '2') {
                $orderColumn = 'status_id';
            }
        }

        if ($request->search != null) {
            if ($request->search['value'] != null && $request->search['value'] != '') {
                $searchkeyword = $request->search['value'];
                $records->where(function ($query) use ($searchkeyword) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhereRaw('LOWER(sls_id) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
                });
            }
        }
        $samples = $records->with(['status', 'sls', 'village', 'subdistrict', 'pcl']);
        $recordsFiltered = $samples->count();

        if ($orderDir == 'asc') {
            $samples = $samples->orderBy($orderColumn);
        } else {
            $samples = $samples->orderByDesc($orderColumn);
        }

        if ($request->length != -1) {
            $samples = $samples->skip($request->start)
                ->take($request->length)->get();
        }

        $samples = $samples->values();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $samples
        ]);
    }

    public function getNonSlsDirectoryTables(Request $request)
    {
        $user = User::find(Auth::id());
        $records = null;

        if ($user->hasRole('adminkab') || $user->hasRole('pml')) {
            $records = NonSlsBusiness::where('regency_id', $user->regency_id);
        }

        if ($request->level === 'regency') {
            $records->whereNull(['subdistrict_id', 'village_id']);
        } elseif ($request->level === 'subdistrict') {
            $records->whereNull('village_id');

            if (!empty($request->subdistrict) && $request->subdistrict !== 'all') {
                $records->where('subdistrict_id', $request->subdistrict);
            }
        } elseif ($request->level === 'village') {
            $records->whereNotNull('village_id');

            if (!empty($request->subdistrict) && $request->subdistrict !== 'all') {
                $records->where('subdistrict_id', $request->subdistrict);
            }

            if (!empty($request->village) && $request->village !== 'all') {
                $records->where('village_id', $request->village);
            }
        }

        // Apply filters
        if ($request->status && $request->status !== 'all') {
            $records->where('status_id', $request->status);
        }

        // if ($request->sls && $request->sls !== 'all') {
        //     $records->where('sls_id', $request->sls);
        // }

        // if ($request->assignment !== null) {
        //     $records->where('pml_id', $request->assignment == '1' ? '!=' : '=', null);
        // }

        $recordsTotal = $records->count();

        $orderColumn = 'sls_id';
        $orderDir = 'desc';
        if ($request->order != null) {
            if ($request->order[0]['dir'] == 'asc') {
                $orderDir = 'asc';
            } else {
            $orderDir = 'desc';
            }
            if ($request->order[0]['column'] == '0') {
                $orderColumn = 'sls_id';
            } else if ($request->order[0]['column'] == '1') {
                $orderColumn = 'name';
            } else if ($request->order[0]['column'] == '2') {
                $orderColumn = 'status_id';
            }
        }

        if ($request->search != null) {
            if ($request->search['value'] != null && $request->search['value'] != '') {
                $searchkeyword = $request->search['value'];
                $records->where(function ($query) use ($searchkeyword) {
                    $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhereRaw('LOWER(village_id) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhereRaw('LOWER(subdistrict_id) LIKE ?', ['%' . strtolower($searchkeyword) . '%'])
                        ->orWhereRaw('LOWER(regency_id) LIKE ?', ['%' . strtolower($searchkeyword) . '%']);
                });
            }
        }
        $samples = $records->with(['status', 'sls', 'village', 'subdistrict', 'regency', 'pml']);

        $recordsFiltered = $samples->count();

        if ($orderDir == 'asc') {
            $samples = $samples->orderBy($orderColumn);
        } else {
            $samples = $samples->orderByDesc($orderColumn);
        }

        if ($request->length != -1) {
            $samples = $samples->skip($request->start ?? 0)
                ->take($request->length ?? 10)->get();
        }

        $samples = $samples->values();

        return response()->json([
            "draw" => $request->draw,
            "recordsTotal" => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data" => $samples
        ]);
    }
}
