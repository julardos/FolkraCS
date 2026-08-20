<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;
use Stancl\Tenancy\Database\Models\Domain;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'           => session('status'),
        ]);
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = Auth::user();

        // Landlord → always go to landlord dashboard
        if ($user->role === 'landlord') {
            return redirect()->intended('/dashboard');
        }

        // Tenant admin/user → redirect to their tenant domain
        if ($user->tenant_id) {
            $currentHost = $request->getHost();
            $suffix      = env('TENANT_DOMAIN_SUFFIX', 'folkra.co');

            // If already on the correct tenant domain, redirect locally
            $tenantDomains = Domain::where('tenant_id', $user->tenant_id)->pluck('domain');
            if ($tenantDomains->contains($currentHost)) {
                return redirect()->intended('/dashboard');
            }

            // Otherwise, redirect to the preferred tenant domain
            $domain = Domain::where('tenant_id', $user->tenant_id)
                ->where('domain', 'like', '%.' . $suffix)
                ->first()
                ?? Domain::where('tenant_id', $user->tenant_id)->first();

            if ($domain) {
                $scheme = $request->isSecure() ? 'https' : 'http';
                return redirect()->away("{$scheme}://{$domain->domain}/dashboard");
            }
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
