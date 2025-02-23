<?php

namespace App\Http\Controllers;

use App\Exports\NonSlsBusinessExport;
use App\Exports\SlsBusinessExport;
use App\Models\Regency;
use App\Models\SlsBusiness;
use App\Models\Status;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminKabController extends Controller
{
    // public function test()
    // {
    //     $pcl = User::where('email', 'pcl01@gmail.com')->first();
    //     $ids = SlsBusiness::where(['regency_id' => $pcl->regency_id, 'status_id' => 1])->skip(0)->take(1000)->pluck('id');
    //     SlsBusiness::whereIn('id', $ids)->update(['status_id' => 2]);

    //     return 'test';
    // }

    public function showAssignment()
    {
        return view('adminkab.assignment');
    }

    public function showDownload()
    {
        return view('adminkab.download');
    }

    public function showAddition()
    {
        return view('adminkab.directory-addition');
    }

    public function updatePage()
    {
        $statuses = Status::orderBy('order', 'asc')->get();

        $user = User::find(Auth::id());
        if ($user->hasRole('adminkab')) {
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
            return view('adminkab.updatingnonsls', ['regencies' => [], 'subdistricts' => $subdistricts, 'statuses' => $statuses]);
        } else if ($user->hasRole('pml')) {
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
            return view('pml.updatingnonsls', ['regencies' => [], 'subdistricts' => $subdistricts, 'statuses' => $statuses]);
        } else if ($user->hasRole('adminprov')) {
            $regencies = Regency::all();
            return view('adminkab.updatingnonsls', ['regencies' => $regencies, 'subdistricts' => [], 'statuses' => $statuses]);
        }

        return abort(403);
    }
}
