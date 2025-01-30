<?php

namespace App\Http\Controllers;

use App\Models\CategorizedBusiness;
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
                User::find(Auth::id())->business()->select('subdistrict_id')->distinct()->pluck('subdistrict_id')
            )->get();
        } else if ($user->hasRole('adminkab')) {
            $subdistricts = Subdistrict::all();
        }

        $statuses = Status::where('name', '!=', 'Baru')->orderBy('order', 'asc')->get();

        return view('pcl.updatingsls', ['subdistricts' => $subdistricts, 'statuses' => $statuses]);
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
}
