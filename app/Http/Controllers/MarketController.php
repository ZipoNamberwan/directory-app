<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarketController extends Controller
{
    public function show()
    {
        return view('market.index');
    }
}
