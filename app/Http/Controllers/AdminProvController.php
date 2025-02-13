<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminProvController extends Controller
{
    public function showPersonification()
    {
        return view('adminprov.personification');
    }
}
