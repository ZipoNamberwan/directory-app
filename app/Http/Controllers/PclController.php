<?php

namespace App\Http\Controllers;

use App\Models\NonSlsBusiness;
use App\Models\SlsBusiness;
use App\Models\Status;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PclController extends Controller
{
    public function update()
    {
        $user = User::find(Auth::id());
        $subdistricts = [];
        if ($user->hasRole('pcl')) {
            $subdistricts = Subdistrict::whereIn(
                'id',
                User::find(Auth::id())->slsBusiness()->select('subdistrict_id')->distinct()->pluck('subdistrict_id')
            )->get();
        } else if ($user->hasRole('adminkab')) {
            $subdistricts = Subdistrict::where('regency_id' , $user->regency_id)->get();
        }

        $statuses = Status::where('name', '!=', 'Baru')->orderBy('order', 'asc')->get();

        return view('pcl.updatingsls', ['subdistricts' => $subdistricts, 'statuses' => $statuses]);
    }

    public function updateDirectory(Request $request, $type, $id)
    {
        $business = null;

        if ($type == 'sls') {
            $business = SlsBusiness::find($id);
        } else {
            $business = NonSlsBusiness::find($id);
        }

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

        $business = new SlsBusiness();
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
        $business = SlsBusiness::find($id);
        $business->delete();

        return response()->json($business);
    }
}
