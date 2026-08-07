<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    protected array $supported = ['th', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang')
            ?? session('locale')
            ?? $this->detectFromBrowser($request)
            ?? config('app.locale', 'th');

        if (!in_array($locale, $this->supported)) {
            $locale = 'th';
        }

        App::setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }

    private function detectFromBrowser(Request $request): ?string
    {
        $header = $request->header('Accept-Language', '');
        $primary = substr($header, 0, 2);
        return in_array($primary, $this->supported) ? $primary : null;
    }
}
