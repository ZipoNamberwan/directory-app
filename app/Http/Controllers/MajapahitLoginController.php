<?php

namespace App\Http\Controllers;

use App\Models\Regency;
use App\Models\User;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MajapahitLoginController extends Controller
{
    public function login(Request $request)
    {
        $jwt = $request->query('token');

        if (Auth::check()) {
            return redirect('/');
        } elseif ($jwt) {
            JWT::$leeway = 60;
            try {
                $key = config('app.majapahit_key');

                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
                $user = User::where('email', $decoded->email)->first();
                if (!$user) {
                    return 'Akun Majapahit Anda tidak memiliki akses ke aplikasi ini. Silahkan hubungi admin satuan kerja';
                } else {
                    $regencyId = null;
                    if ($decoded->satker != '3500') {
                        $regencyId = Regency::where('long_code', $decoded->satker)->first()->id;
                    }

                    $user->update([
                        'firstname' => $decoded->nama,
                        'regency_id' => $regencyId,
                    ]);

                    Auth::login($user, true);

                    return redirect('/');
                }
            } catch (Exception $e) {
                if (str_contains($e->getMessage(), 'Expired token') || str_contains($e->getMessage(), 'Signature verification failed')) {
                    return 'Token Majapahit tidak valid, silahkan buka ulang Majapahit';
                } else {
                    return 'Terjadi kesalahan pada aplikasi ini, silahkan coba lagi';
                }
            }
        } /* elseif (app()->environment('local')) {
            $email = $request->query('magiclink');
            if ($email) {
                $user = User::where('email', $email)->first();
                if ($user) {
                    Auth::login($user);

                    return redirect('/');
                } else {
                    return 'Akun Majapahit Anda tidak memiliki akses ke aplikasi ini. Silahkan hubungi admin satuan kerja';
                }
            }
        } */

        return 'Anda belum masuk ke akun Kendedes, silahkan buka melalui Majapahit';
    }
}
