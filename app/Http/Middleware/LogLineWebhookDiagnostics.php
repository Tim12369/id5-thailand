<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * TEMPORARY DIAGNOSTIC middleware — logs every inbound request that looks
 * like a LINE webhook delivery attempt (by header or path), regardless of
 * which route it actually resolves to. Remove once webhook verification is
 * confirmed working end to end.
 */
class LogLineWebhookDiagnostics
{
    public function handle(Request $request, Closure $next): Response
    {
        $looksLikeLine = $request->hasHeader('X-Line-Signature')
            || str_contains($request->path(), 'line')
            || str_contains($request->path(), 'webhook');

        if ($looksLikeLine) {
            Log::info('line.webhook.diagnostic.incoming', [
                'method'   => $request->method(),
                'path'     => '/'.$request->path(),
                'full_url' => $request->fullUrl(),
                'headers'  => $request->headers->all(),
            ]);
        }

        $response = $next($request);

        if ($looksLikeLine) {
            Log::info('line.webhook.diagnostic.outgoing', [
                'method' => $request->method(),
                'path'   => '/'.$request->path(),
                'status' => $response->getStatusCode(),
                'allow'  => $response->headers->get('Allow'),
            ]);
        }

        return $response;
    }
}
