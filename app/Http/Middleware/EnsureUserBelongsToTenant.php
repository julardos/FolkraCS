<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUserBelongsToTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // If there's no authenticated user, skip here (auth middleware should handle it)
        if (! Auth::check()) {
            return $next($request);
        }

        // If tenancy is not initialized, skip this check
        if (! function_exists('tenant') || ! tenant()) {
            return $next($request);
        }

        $user = Auth::user();
        $currentTenantId = tenant('id');

        // If user has no tenant_id or mismatched, logout and redirect to login
        if (! $user->tenant_id || (string) $user->tenant_id !== (string) $currentTenantId) {
            Auth::logout();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthorized for this tenant.'], 403);
            }

            return redirect('/login')->withErrors(['tenant' => 'Your account does not belong to this tenant.']);
        }

        return $next($request);
    }
}
