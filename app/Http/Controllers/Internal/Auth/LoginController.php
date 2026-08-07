<?php

namespace App\Http\Controllers\Internal\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('internal.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors(['email' => __('The provided credentials do not match our records.')])
                ->onlyInput('email');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()
                ->withErrors(['email' => 'This account has been disabled. Contact your administrator.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('internal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('internal.login');
    }
}
