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
use Illuminate\Support\Str;

class PclController extends Controller
{
    public function update()
    {
        $subdistricts = Subdistrict::whereIn(
            'id',
            User::find(Auth::id())->business()->select('subdistrict_id')->distinct()->pluck('subdistrict_id')
        )->get();

        $statuses = Status::where('name', '!=', 'Baru')->get();

        return view('pcl.updating', ['subdistricts' => $subdistricts, 'statuses' => $statuses]);
    }

    public function updateDirectory(Request $request, $id)
    {
        $business = CategorizedBusiness::find($id);

        if ($request->new == "true") {
            $business->name = $request->name;
        } else {
            $business->status_id = $request->status;
        }
        $business->save();

        return response()->json($business);
    }
    public function addDirectory(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'subdistrict' => 'required',
            'village' => 'required',
            'sls' => 'required',
        ]);

        $business = new CategorizedBusiness();
        $business->name = $request->name;
        $business->regency_id = User::find(Auth::id())->regency_id;
        $business->subdistrict_id = $request->subdistrict;
        $business->village_id = $request->village;
        $business->sls_id = $request->sls;
        $business->status_id = 4;
        $business->is_new = true;
        $business->pcl_id = Auth::id();
        $business->save();

        return response()->json($business);
    }
    public function deleteDirectory(string $id)
    {
        $business = CategorizedBusiness::find($id);
        $business->delete();

        return response()->json($business);
    }

    public function getVillage($subdistrict_id)
    {
        $village = Village::whereIn(
            'id',
            User::find(Auth::id())->business()->select('village_id')->where('village_id', 'like', "{$subdistrict_id}%")->distinct()->pluck('village_id')
        )->get();

        return response()->json($village);
    }
    public function getSls($village_id)
    {
        $sls = Sls::whereIn(
            'id',
            User::find(Auth::id())->business()->select('sls_id')->where('sls_id', 'like', "{$village_id}%")->distinct()->pluck('sls_id')
        )->get();
        return response()->json($sls);
    }
    public function getDirectory($id_sls)
    {
        $business = User::find(Auth::id())->business()->where('sls_id', '=', $id_sls)->with(['status', 'sls', 'village', 'subdistrict'])->get();
        return response()->json($business);
    }
    public function getDirectoryTables(Request $request, $type)
    {
        $records = null;
        if ($type == 'pcl') {
            $records = User::find(Auth::id())->business();
        } else if ($type == 'adminkab') {
            $records = CategorizedBusiness::where(['regency_id' => User::find(Auth::id())->regency_id]);
        }

        if ($request->status) {
            if ($request->status != 'all') {
                $records->where(['status_id' => $request->status]);
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
