<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ChangePassword extends Controller
{

    public function show()
    {
        return view('auth.change-password');
    }

    public function update(Request $request)
    {
        $attributes = $request->validate([
            'password' => ['required', Password::min(8)->mixedCase(),],
            'confirm-password' => ['same:password']
        ]);

        $existingUser = User::where('email', Auth::user()->email)->first();
        if ($existingUser) {
            $existingUser->update([
                'password' => Hash::make($attributes['password']),
                'must_change_password' => false,
            ]);
            return redirect('login');
        } else {
            return back()->with('error', 'Your email does not match the email who requested the password change');
        }
    }
}
