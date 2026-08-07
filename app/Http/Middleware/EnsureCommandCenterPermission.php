<?php

namespace App\Http\Middleware;

use App\Policies\CommandCenterPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route-level RBAC gate for the Command Center module.
 * Usage: ->middleware('command-center.permission:view')
 *        ->middleware('command-center.permission:manage')
 */
class EnsureCommandCenterPermission
{
    public function __construct(private CommandCenterPolicy $policy) {}

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $user = $request->user();
        $allowed = $user && match ($ability) {
            'manage' => $this->policy->manage($user),
            default  => $this->policy->view($user),
        };

        if (!$allowed) {
            abort(403);
        }

        return $next($request);
    }
}
