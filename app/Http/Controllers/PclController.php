<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseSelector;
use App\Models\NonSlsBusiness;
use App\Models\Regency;
use App\Models\SlsBusiness;
use App\Models\Status;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PclController extends Controller
{
    public function updatePage()
    {
        $user = User::find(Auth::id());
        $subdistricts = [];

        if ($user->hasRole('pcl')) {
            $statuses = Status::where('name', '!=', 'Baru')->orderBy('order', 'asc')->get();
            $subdistricts = Subdistrict::whereIn(
                'id',
                SlsBusiness::on(DatabaseSelector::getConnection($user->regency_id))->select('subdistrict_id')->where('pcl_id', $user->id)->distinct()->pluck('subdistrict_id')
            )->get();

            return view('pcl.updatingsls', ['subdistricts' => $subdistricts, 'statuses' => $statuses]);
        } else if ($user->hasRole('adminkab')) {
            $statuses = Status::orderBy('order', 'asc')->get();
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();

            return view('adminkab.updatingsls', ['regencies' => [], 'subdistricts' => $subdistricts, 'statuses' => $statuses]);
        } else if ($user->hasRole('adminprov')) {
            $statuses = Status::orderBy('order', 'asc')->get();
            $regencies = Regency::all();

            return view('adminkab.updatingsls', ['regencies' => $regencies, 'statuses' => $statuses, 'subdistricts' => []]);
        }
    }
}
