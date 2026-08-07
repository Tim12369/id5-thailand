<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InternalAuthenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        if (! $request->expectsJson()) {
            return route('internal.login');
        }

        return null;
    }

    /**
     * A disabled account (User::is_active = false) must be logged out
     * immediately on its very next request, not just blocked at the next
     * login attempt -- otherwise an already-open session stays valid for
     * as long as the browser tab does.
     */
    public function handle($request, Closure $next, ...$guards): Response
    {
        $user = auth()->user();

        if ($user && ! $user->is_active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('internal.login')
                ->withErrors(['email' => 'This account has been disabled. Contact your administrator.']);
        }

        return parent::handle($request, $next, ...$guards);
    }
}
