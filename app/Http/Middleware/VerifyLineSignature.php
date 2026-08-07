<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyLineSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('line.webhook.middleware.enter', [
            'ip'             => $request->ip(),
            'content_length' => $request->header('Content-Length'),
            'has_signature'  => $request->hasHeader('X-Line-Signature'),
        ]);

        $signature = $request->header('X-Line-Signature', '');
        $secret = (string) config('services.line.channel_secret');

        Log::info('line.webhook.signature.start');

        $expected = base64_encode(hash_hmac('sha256', $request->getContent(), $secret, true));
        $valid = $signature !== '' && hash_equals($expected, $signature);

        Log::info('line.webhook.signature.done', ['valid' => $valid]);

        if (! $valid) {
            Log::warning('line.webhook.signature.rejected');
            abort(400, 'Invalid LINE signature.');
        }

        $response = $next($request);

        Log::info('line.webhook.middleware.exit', ['status' => $response->getStatusCode()]);

        return $response;
    }
}
