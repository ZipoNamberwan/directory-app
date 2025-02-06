<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Subdistrict;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminKabController extends Controller
{
    public function index()
    {
        return view('adminkab.index');
    }

    public function showAssignment()
    {
        return view('adminkab.assignment');
    }

    public function updatePage()
    {
        $statuses = Status::orderBy('order', 'asc')->get();

        $user = User::find(Auth::id());
        if ($user->hasRole('adminkab')) {
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
            return view('adminkab.updatingnonsls', ['subdistricts' => $subdistricts, 'statuses' => $statuses]);
        } else if ($user->hasRole('pml')) {
            $subdistricts = Subdistrict::where('regency_id', $user->regency_id)->get();
            return view('pml.updatingnonsls', ['subdistricts' => $subdistricts, 'statuses' => $statuses]);
        }

        return abort(403);
    }
}
