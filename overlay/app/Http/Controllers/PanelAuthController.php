<?php

namespace App\Http\Controllers;

use App\Models\PanelUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PanelAuthController extends Controller
{
    public function show(Request $request)
    {
        if ($request->session()->has('panel_user_id')) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email','max:255'],
            'password' => ['required','string','max:255'],
        ]);

        $email = Str::lower(trim($data['email']));
        $key = 'panel-login:'.$email.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors([
                    'email' => 'Demasiados intentos. Espera '.RateLimiter::availableIn($key).' segundos.'
                ])
                ->onlyInput('email');
        }

        $user = PanelUser::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$user || !$user->active || !Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 900);

            return back()
                ->withErrors(['email'=>'Credenciales inválidas o usuario inactivo.'])
                ->onlyInput('email');
        }

        RateLimiter::clear($key);

        $request->session()->regenerate();

        $request->session()->put([
            'panel_user_id' => $user->id,
            'panel_last_activity' => time(),
        ]);

        $user->update([
            'last_login_at' => now()
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('ok','Sesión cerrada correctamente.');
    }
}
