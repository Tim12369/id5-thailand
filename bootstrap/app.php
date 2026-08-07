<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::group([], __DIR__.'/../routes/line.php');
            Route::middleware(['web', 'set.locale'])->group(__DIR__.'/../routes/internal.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
    //
        // Trust the Cloudflare tunnel as a reverse proxy so Laravel reads the
        // X-Forwarded-Proto header and generates https:// asset/URL links —
        // without this, asset URLs are emitted as http:// and get blocked as
        // mixed content on the https tunnel (works locally, breaks remotely).
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO);

        $middleware->alias([
            'set.locale' => \App\Http\Middleware\SetLocale::class,
            'line.signature' => \App\Http\Middleware\VerifyLineSignature::class,
            'internal.auth' => \App\Http\Middleware\InternalAuthenticate::class,
            'internal.guest' => \App\Http\Middleware\InternalRedirectIfAuthenticated::class,
            'command-center.permission' => \App\Http\Middleware\EnsureCommandCenterPermission::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'line/webhook',
        ]);

        // TEMPORARY DIAGNOSTIC — logs every inbound request that looks like a
        // LINE webhook delivery attempt (by header or path), regardless of
        // which route it actually resolves to. Remove once webhook
        // verification is confirmed working end to end.
        $middleware->append(\App\Http\Middleware\LogLineWebhookDiagnostics::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
    