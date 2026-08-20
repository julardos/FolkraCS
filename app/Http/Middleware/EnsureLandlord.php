<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureLandlord
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->role !== 'landlord') {
            abort(403, 'Access restricted to landlord accounts.');
        }
        return $next($request);
    }
}
