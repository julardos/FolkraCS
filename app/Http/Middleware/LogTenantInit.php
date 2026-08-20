<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogTenantInit
{
    public function handle(Request $request, Closure $next)
    {
        Log::channel('single')->info('[MIDDLEWARE] LogTenantInit', [
            'host'           => $request->getHost(),
            'path'           => $request->path(),
            'method'         => $request->method(),
            'tenant_active'  => function_exists('tenant') && tenant() ? tenant('id') : 'none',
            'session_id'     => session()->getId(),
            'authenticated'  => auth()->check() ? auth()->id() : 'no',
        ]);

        $response = $next($request);

        Log::channel('single')->info('[MIDDLEWARE] LogTenantInit AFTER', [
            'host'           => $request->getHost(),
            'path'           => $request->path(),
            'status'         => $response->getStatusCode(),
            'tenant_active'  => function_exists('tenant') && tenant() ? tenant('id') : 'none',
            'authenticated'  => auth()->check() ? auth()->id() : 'no',
        ]);

        return $response;
    }
}
