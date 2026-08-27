<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Notifications\TenantInviteNotification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant;

class TenantController extends Controller
{
    public function index()
    {
        $clients = Client::latest()->get()->map(fn($c) => [
            'id'               => $c->id,
            'name'             => $c->name,
            'slug'             => $c->slug,
            'channels'         => $c->channels,
            'business_type'    => $c->business_type,
            'status'           => $c->status,
            'tenant_id'        => $c->tenant_id,
            'wa_base_url'      => $c->wa_base_url,
            'wa_session'       => $c->wa_session,
            'openrouter_model' => $c->openrouter_model,
            'masked_wa_key'    => $c->masked_wa_key,
            'masked_ai_key'    => $c->masked_ai_key,
            'domain'           => $c->tenant_id
                ? optional(Domain::where('tenant_id', $c->tenant_id)
                    ->where('domain', 'like', '%.'.self::domainSuffix())
                    ->first() ?? Domain::where('tenant_id', $c->tenant_id)->first())->domain
                : null,
            'user_count'       => User::where('tenant_id', $c->tenant_id)->count(),
            'created_at'       => $c->created_at->format('d M Y'),
        ]);

        return Inertia::render('Landlord/Clients/Index', [
            'clients' => $clients,
            'landlordDomain' => config('tenancy.central_domains')[3] ?? 'landlord.localhost',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'channels'           => 'required|in:whatsapp,whatsapp_instagram',
            'name'               => 'required|string|max:255',
            'business_type'      => 'nullable|string|max:255',
            'slug'               => 'required|string|alpha_dash|unique:clients,slug|unique:tenants,id',
            'wa_base_url'        => 'nullable|url',
            'wa_api_key'         => 'nullable|string',
            'wa_session'         => 'nullable|string',
            'openrouter_api_key' => 'nullable|string',
            'openrouter_model'   => 'nullable|string',
            'ai_instruction'     => 'nullable|string',
            // Admin user
            'admin_name'         => 'required|string|max:255',
            'admin_email'        => 'required|email|unique:users,email',
            'admin_password'     => 'nullable|string|min:8',
        ]);

        $inviteUser   = null;
        $tenantDomain = null;

        DB::transaction(function () use ($data, &$inviteUser, &$tenantDomain) {
            $tenantId = $data['slug'];

            // 1. Create stancl tenant
            Tenant::create(['id' => $tenantId]);

            // 2. Create domain(s)
            $suffix = self::domainSuffix();
            Domain::create(['domain' => "{$tenantId}.{$suffix}", 'tenant_id' => $tenantId]);
            if ($suffix !== 'localhost') {
                Domain::create(['domain' => "{$tenantId}.localhost", 'tenant_id' => $tenantId]);
            }

            // 3. Create client record
            $client = Client::create([
                'tenant_id'          => $tenantId,
                'slug'               => $tenantId,
                'channels'           => $data['channels'],
                'name'               => $data['name'],
                'business_type'      => $data['business_type'] ?? null,
                'wa_base_url'        => $data['wa_base_url'] ?? null,
                'wa_api_key'         => $data['wa_api_key'] ?? null,
                'wa_session'         => $data['wa_session'] ?? null,
                'openrouter_api_key' => $data['openrouter_api_key'] ?? null,
                'openrouter_model'   => $data['openrouter_model'] ?? 'openai/gpt-4o-mini',
                'ai_instruction'     => $data['ai_instruction'] ?? null,
                'status'             => 'active',
            ]);

            // 4. Create admin user for this tenant
            $tempPassword = $data['admin_password'] ?? Str::random(12);
            $inviteUser   = User::create([
                'name'      => $data['admin_name'],
                'email'     => $data['admin_email'],
                'password'  => Hash::make($tempPassword),
                'role'      => 'admin',
                'tenant_id' => $tenantId,
                'client_id' => $client->id,
            ]);

            // Use the actual stored domain rather than reconstructing from env
            $tenantDomain = Domain::where('tenant_id', $tenantId)
                ->where('domain', 'not like', '%.localhost')
                ->value('domain') ?? "{$tenantId}.{$suffix}";
        });

        // 5. Send onboarding invite outside the transaction so email failure
        //    never rolls back the client creation.
        if ($inviteUser) {
            $token = Password::createToken($inviteUser);
            $inviteUser->notify(new TenantInviteNotification($token, $tenantDomain, $data['name']));
        }

        return back()->with('success', 'Client created successfully.');
    }

    public function resetUserPassword(User $user)
    {
        // Only allow resetting tenant users, not other landlords
        abort_if(!$user->tenant_id, 403);

        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', "Password reset link sent to {$user->email}.");
        }

        return back()->withErrors(['email' => __($status)]);
    }

    private static function domainSuffix(): string
    {
        // Prefer explicit env var; fall back to the host in APP_URL so production
        // works correctly even if TENANT_DOMAIN_SUFFIX is not set.
        return env('TENANT_DOMAIN_SUFFIX')
            ?: parse_url(config('app.url'), PHP_URL_HOST)
            ?: 'localhost';
    }

    public function destroy(Client $client)
    {
        DB::transaction(function () use ($client) {
            if ($client->tenant_id) {
                Domain::where('tenant_id', $client->tenant_id)->delete();
                Tenant::find($client->tenant_id)?->delete();
                User::where('tenant_id', $client->tenant_id)->delete();
            }
            $client->delete();
        });

        return back()->with('success', 'Client and all related data removed.');
    }
}
