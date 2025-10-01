<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class WmsLoginController extends Controller
{
    public function show(){
        if (Auth::check() && Auth::user()->role === 'wms') {
            return redirect()->route('wms.dashboard');
        }
        return view('wms-login');
    }

    public function login(Request $r){
        // validasi dasar
        $r->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // throttle key: email + IP
        $key = Str::lower($r->input('email')).'|'.$r->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()
                ->withErrors(['email' => "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik."])
                ->withInput($r->only('email'));
        }

        // opsional: cek role user supaya pesan lebih jelas
        $user = Account::where('email_pengguna', $r->email)->first();
        if ($user && $user->role !== 'wms') {
            // hit throttle supaya tetap dihitung percobaan
            RateLimiter::hit($key, 60);
            return back()
                ->withErrors(['email' => 'Akun ini tidak memiliki akses WMS.'])
                ->withInput($r->only('email'));
        }

        // lakukan attempt (email_pengguna kolom login kustom)
        $ok = Auth::attempt([
            'email_pengguna' => $r->email,
            'password'       => $r->password,
            'role'           => 'wms', // pastikan hanya WMS yang bisa lewat
        ], false);

        if ($ok) {
            RateLimiter::clear($key);
            $r->session()->regenerate();
            return redirect()->intended(route('wms.dashboard'));
        }

        // gagal: hit throttle & kirim pesan
        RateLimiter::hit($key, 60);
        return back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->withInput($r->only('email'));
    }

    public function logout(Request $r){
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect('/wms/login');
    }
}
