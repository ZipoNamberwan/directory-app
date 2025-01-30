<?php

namespace App\Http\Controllers;

use App\Models\CategorizedBusiness;
use App\Models\Sls;
use App\Models\Status;
use App\Models\Subdistrict;
use App\Models\User;
use App\Models\Village;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

        // $regencyReport = DB::table('categorized_business')
        //     ->select(
        //         'regency_id',
        //         DB::raw("SUM(CASE WHEN status_id = 1 THEN 1 ELSE 0 END) as active_count"),
        //         DB::raw("SUM(CASE WHEN status_id = 2 THEN 1 ELSE 0 END) as inactive_count")
        //     )
        //     ->groupBy('regency_id')
        //     ->get();

        // dd($regencyReport);

        $user = User::find(Auth::id());

        if ($user->hasRole('pcl')) {
            $businessBase = CategorizedBusiness::where(['pcl_id' => Auth::id()]);
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
            $businessBase = CategorizedBusiness::where(['regency_id' => User::find(Auth::id())->regency_id]);
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
                $user->business()->select('village_id')->where('village_id', 'like', "{$subdistrict_id}%")->distinct()->pluck('village_id')
            )->get();
        } else if ($user->hasRole('adminkab')) {
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
                $user->business()->select('sls_id')->where('sls_id', 'like', "{$village_id}%")->distinct()->pluck('sls_id')
            )->get();
        } else if ($user->hasRole('adminkab')) {
            $sls = Sls::where('village_id', $village_id)->get();
        }

        return response()->json($sls);
    }
    public function getDirectory($id_sls)
    {
        $user = User::find(Auth::id());
        $business = [];

        if ($user->hasRole('pcl')) {
            $business = $user->business()->where('sls_id', '=', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();
        } else if ($user->hasRole('adminkab')) {
            $business = CategorizedBusiness::where('sls_id', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();;
        }
        return response()->json($business);
    }

    public function getDirectoryTables(Request $request)
    {
        $records = null;

        $user = User::find(Auth::id());

        if ($user->hasRole('pcl')) {
            $records = User::find(Auth::id())->business();
        } else if ($user->hasRole('adminkab')) {
            $records = CategorizedBusiness::where(['regency_id' => User::find(Auth::id())->regency_id]);
        }

        if ($request->status) {
            if ($request->status != 'all') {
                $records->where(['status_id' => $request->status]);
            }
        }

        if ($request->subdistrict) {
            if ($request->subdistrict != 'all') {
                $records->where(['subdistrict_id' => $request->subdistrict]);
            }
        }
        if ($request->village) {
            if ($request->village != 'all') {
                $records->where(['village_id' => $request->village]);
            }
        }
        if ($request->sls) {
            if ($request->sls != 'all') {
                $records->where(['sls_id' => $request->sls]);
            }
        }
        if ($request->assignment) {
            if ($request->assignment == '1') {
                $records->where('pcl_id', '!=', null);
            } else if ($request->assignment == '0') {
                $records->where('pcl_id', '=', null);
            }
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

        $searchkeyword = $request->search['value'];
        $samples = $records->with(['status', 'sls', 'village', 'subdistrict', 'pcl'])->get();
        if ($searchkeyword != null) {
            $samples = $samples->filter(function ($q) use (
                $searchkeyword
            ) {
                return Str::contains(strtolower($q->name), strtolower($searchkeyword)) ||
                    Str::contains(strtolower($q->sls_id), strtolower($searchkeyword));
            });
        }
        $recordsFiltered = $samples->count();

        if ($orderDir == 'asc') {
            $samples = $samples->sortBy($orderColumn);
        } else {
            $samples = $samples->sortByDesc($orderColumn);
        }

        if ($request->length != -1) {
            $samples = $samples->skip($request->start)
                ->take($request->length);
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
