<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogTenantInit
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = 'none';
        try {
            if (function_exists('tenant') && tenancy()->initialized) {
                $tenantId = tenant('id') ?? 'initialized-but-null';
            }
        } catch (\Throwable) {}

        Log::channel('single')->info('[TENANT MIDDLEWARE] before', [
            'host'    => $request->getHost(),
            'method'  => $request->method(),
            'path'    => $request->path(),
            'tenant'  => $tenantId,
            'session' => session()->getId(),
            'auth'    => auth()->check() ? auth()->user()->email : 'guest',
        ]);

        $response = $next($request);

        $tenantIdAfter = 'none';
        try {
            if (function_exists('tenant') && tenancy()->initialized) {
                $tenantIdAfter = tenant('id') ?? 'initialized-but-null';
            }
        } catch (\Throwable) {}

        Log::channel('single')->info('[TENANT MIDDLEWARE] after', [
            'host'   => $request->getHost(),
            'path'   => $request->path(),
            'status' => $response->getStatusCode(),
            'tenant' => $tenantIdAfter,
            'auth'   => auth()->check() ? auth()->user()->email : 'guest',
        ]);

        return $response;
    }
}
