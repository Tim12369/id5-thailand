<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class InternalRedirectIfAuthenticated
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            return redirect()->route('internal.dashboard');
        }

        return $next($request);
    }
}
