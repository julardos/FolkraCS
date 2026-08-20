<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureTenantAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin' || !Auth::user()->tenant_id) {
            abort(403, 'Access restricted to tenant admin accounts.');
        }
        return $next($request);
    }
}
