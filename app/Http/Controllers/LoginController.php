<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Display login page.
     *
     * @return Renderable
     */
    public function show()
    {
        $redirectUrl = '';
        if (config('app.env') == 'production') {
            $redirectUrl = "https://majapahit.web.bps.go.id/dashboard?callback_uri=" . url('/majapahit');
        } else {
            $redirectUrl = "https://majapah.it/dashboard?callback_uri=" . url('/majapahit');
        }

        return view('auth.login', ['redirectUrl' => $redirectUrl]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $request->session()->regenerate();

            if (Auth::user()->must_change_password) {
                return redirect('/change-password');
            }

            return redirect('/');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
