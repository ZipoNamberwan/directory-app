<?php

namespace App\Http\Controllers;

use App\Exports\CategorizedBusinessTemplateExport;
use Illuminate\Http\Request;

class AdminKabController extends Controller
{
    public function index()
    {
        return view('adminkab.index');
    }

    public function showAssignment(){
        return view('adminkab.assignment');
    }
}
