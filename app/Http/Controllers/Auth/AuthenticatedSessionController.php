<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Stancl\Tenancy\Database\Models\Domain;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        Log::channel('single')->info('[LOGIN PAGE]', [
            'host'    => request()->getHost(),
            'url'     => request()->fullUrl(),
            'session' => session()->getId(),
        ]);

        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status'           => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        Log::channel('single')->info('[LOGIN ATTEMPT]', [
            'host'    => $request->getHost(),
            'email'   => $request->email,
            'session' => session()->getId(),
            'csrf'    => $request->header('X-XSRF-TOKEN') ? 'present' : 'missing',
        ]);

        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::channel('single')->warning('[LOGIN FAILED]', [
                'email'  => $request->email,
                'errors' => $e->errors(),
                'host'   => $request->getHost(),
            ]);
            throw $e;
        }

        $request->session()->regenerate();
        $user = Auth::user();

        Log::channel('single')->info('[LOGIN SUCCESS]', [
            'user_id'   => $user->id,
            'email'     => $user->email,
            'role'      => $user->role,
            'tenant_id' => $user->tenant_id,
            'host'      => $request->getHost(),
        ]);

        // Landlord
        if ($user->role === 'landlord') {
            Log::channel('single')->info('[REDIRECT] landlord → /dashboard');
            return redirect()->intended('/dashboard');
        }

        // Tenant user
        if ($user->tenant_id) {
            $currentHost   = $request->getHost();
            $suffix        = env('TENANT_DOMAIN_SUFFIX', 'folkra.co');
            $tenantDomains = Domain::where('tenant_id', $user->tenant_id)->pluck('domain');

            Log::channel('single')->info('[TENANT REDIRECT CHECK]', [
                'current_host'   => $currentHost,
                'tenant_domains' => $tenantDomains->toArray(),
                'suffix'         => $suffix,
            ]);

            if ($tenantDomains->contains($currentHost)) {
                Log::channel('single')->info('[REDIRECT] same domain → /dashboard');
                return redirect()->intended('/dashboard');
            }

            $domain = Domain::where('tenant_id', $user->tenant_id)
                ->where('domain', 'like', '%.' . $suffix)
                ->first()
                ?? Domain::where('tenant_id', $user->tenant_id)->first();

            if ($domain) {
                $scheme = $request->isSecure() ? 'https' : 'http';
                $target = "{$scheme}://{$domain->domain}/dashboard";
                Log::channel('single')->info("[REDIRECT] away → {$target}");
                return redirect()->away($target);
            }

            Log::channel('single')->warning('[REDIRECT] no domain found, falling back to /dashboard');
        }

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
