<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureLandlordDomain
{
    public function handle(Request $request, Closure $next)
    {
        $centralDomains = config('tenancy.central_domains', []);

        if (!in_array($request->getHost(), $centralDomains)) {
            abort(404);
        }

        return $next($request);
    }
}
