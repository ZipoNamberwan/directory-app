<?php

namespace App\Http\Controllers;

use App\Models\Status;
use App\Models\Subdistrict;

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

    public function update()
    {
        $subdistricts = Subdistrict::all();
        $statuses = Status::orderBy('order', 'asc')->get();

        return view('adminkab.updatingnonsls', ['subdistricts' => $subdistricts, 'statuses' => $statuses]);
    }

    public function report()
    {
        return 'coming soon';
    }
}
