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
use Symfony\Component\HttpKernel\Exception\HttpException;

class PclController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('pcl.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update()
    {
        $subdistricts = Subdistrict::whereIn(
            'id',
            User::find(Auth::id())->business()->select('subdistrict_id')->distinct()->pluck('subdistrict_id')
        )->get();

        $statuses = Status::where('name', '!=', 'Baru')->get();

        return view('pcl.updating', ['subdistricts' => $subdistricts, 'statuses' => $statuses]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function updateDirectory(Request $request, $id)
    {
        $business = CategorizedBusiness::find($id);
        $business->status_id = $request->status;
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

    public function getVillage($id)
    {
        $village = Village::whereIn(
            'id',
            User::find(Auth::id())->business()->select('village_id')->where('village_id', 'like', "{$id}%")->distinct()->pluck('village_id')
        )->get();

        return response()->json($village);
    }
    public function getSls($id)
    {
        $sls = Sls::whereIn(
            'id',
            User::find(Auth::id())->business()->select('sls_id')->where('sls_id', 'like', "{$id}%")->distinct()->pluck('sls_id')
        )->get();
        return response()->json($sls);
    }
    public function getDirectory($id)
    {
        $business = User::find(Auth::id())->business()->where('sls_id', '=', $id)->with(['status', 'sls', 'village', 'subdistrict'])->get();

        return response()->json($business);
    }
}
