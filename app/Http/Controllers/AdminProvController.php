<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class AdminProvController extends Controller
{
    public function showPersonification()
    {
        return view('adminprov.personification');
    }

    // public function startImpersonation($userId)
    // {
    //     Auth::loginUsingId($userId);

    //     return redirect('/')->with('success', 'You are now impersonating the user.');
    // }

    // public function stopImpersonation()
    // {
    //     session()->forget('impersonate');

    //     return redirect('/')->with('success', 'You have exited impersonation mode.');
    // }
}
