<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Authenticates inbound notifications from the WordPress editing install.
 *
 * WordPress signs `"<timestamp>\n<raw body>"` with HMAC-SHA256 under a shared
 * secret. Including the timestamp inside the signed material — and rejecting
 * anything outside a short window — means a captured request cannot be replayed
 * later.
 *
 * The notification itself carries no content, only "this changed"; the payload
 * is then pulled from WordPress over an authenticated connection. So even a
 * forged request that somehow passed this check could at worst trigger a
 * redundant refresh, never inject content.
 */
class VerifyCmsWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secret = (string) config('cms.webhook.secret');

        if ($secret === '') {
            Log::channel('cms')->warning('Webhook rejected: no shared secret is configured.');

            return response()->json(['message' => 'Not configured.'], 503);
        }

        $timestamp = (string) $request->header('X-ID5-Timestamp', '');
        $signature = (string) $request->header('X-ID5-Signature', '');

        if ($timestamp === '' || $signature === '') {
            return $this->reject($request, 'missing signature headers');
        }

        if (! ctype_digit($timestamp)) {
            return $this->reject($request, 'malformed timestamp');
        }

        $drift = abs(time() - (int) $timestamp);

        if ($drift > (int) config('cms.webhook.tolerance')) {
            return $this->reject($request, "timestamp outside the replay window ({$drift}s)");
        }

        $expected = hash_hmac('sha256', $timestamp."\n".$request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            return $this->reject($request, 'signature mismatch');
        }

        return $next($request);
    }

    /**
     * Log enough to diagnose a misconfiguration, and return a response that
     * tells an attacker nothing about which check failed.
     */
    private function reject(Request $request, string $reason): Response
    {
        Log::channel('cms')->warning('Webhook rejected.', [
            'reason' => $reason,
            'ip'     => $request->ip(),
        ]);

        return response()->json(['message' => 'Unauthorized.'], 401);
    }
}
