<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientController extends Controller
{
    public function index()
    {
        return Inertia::render('Landlord/Clients/Index', [
            'clients' => Client::latest()->get()->map(fn($c) => [
                'id'              => $c->id,
                'name'            => $c->name,
                'business_type'   => $c->business_type,
                'status'          => $c->status,
                'wa_session'      => $c->wa_session,
                'wa_base_url'     => $c->wa_base_url,
                'openrouter_model'=> $c->openrouter_model,
                'masked_wa_key'   => $c->masked_wa_key,
                'masked_ai_key'   => $c->masked_ai_key,
                'created_at'      => $c->created_at->format('d M Y'),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'business_type'      => 'nullable|string|max:255',
            'wa_base_url'        => 'nullable|url',
            'wa_api_key'         => 'nullable|string',
            'wa_session'         => 'nullable|string',
            'openrouter_api_key' => 'nullable|string',
            'openrouter_model'   => 'nullable|string',
            'ai_instruction'     => 'nullable|string',
            // admin user to create for this client
            'admin_name'         => 'required|string|max:255',
            'admin_email'        => 'required|email|max:255|unique:users,email',
        ]);

        // Separate client data from admin user data
        $clientData = $data;
        unset($clientData['admin_name'], $clientData['admin_email']);

        try {
            $client = \Illuminate\Support\Facades\DB::transaction(function () use ($clientData, $request) {
                $client = Client::create($clientData);

                // Create stancl/tenancy Tenant record (central tenancy table)
                $slug = \Illuminate\Support\Str::slug($client->name) . '-' . $client->id;
                $stanclTenant = \Stancl\Tenancy\Database\Models\Tenant::create([
                    'id' => $slug,
                    'data' => ['client_id' => $client->id, 'name' => $client->name],
                ]);

                // Add app-specific metadata into stancl tenant data (shared storage)
                $stanclTenant->data = array_merge($stanclTenant->data ?? [], [
                    'client_id' => $client->id,
                    'name' => $client->name,
                    'slug' => $slug,
                    'config' => ['features' => ['booking' => false]],
                    'status' => 'active',
                ]);
                $stanclTenant->save();

                // Attach a domain for subdomain-based identification (e.g. tenant.localhost)
                try {
                    $appUrl = config('app.url') ?? env('APP_URL', 'http://localhost');
                    $host = parse_url($appUrl, PHP_URL_HOST) ?: 'localhost';
                    $domain = $slug . '.' . $host;
                    \Stancl\Tenancy\Database\Models\Domain::create([
                        'domain' => $domain,
                        'tenant_id' => $stanclTenant->id,
                    ]);
                } catch (\Exception $e) {
                    // Not critical — domain creation failed (e.g., missing permissions)
                    \Log::warning('Failed to create tenant domain', ['error' => $e->getMessage()]);
                }

                // Create initial admin user for the client and associate with tenant
                $password = \Illuminate\Support\Str::random(12);
                $user = \App\Models\User::create([
                    'name' => $request->input('admin_name'),
                    'email' => $request->input('admin_email'),
                    'password' => $password, // hashed by model cast
                    'client_id' => $client->id,
                    'tenant_id' => $stanclTenant->id,
                    'role' => 'admin',
                ]);

                // Generate password reset token and send invite email
                try {
                    $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
                    $user->notify(new \App\Notifications\TenantInviteNotification($token));
                } catch (\Exception $e) {
                    \Log::warning('Failed to send tenant invite email', ['error' => $e->getMessage()]);
                }

                // TODO: dispatch an invite email / password reset link job here
                return $client;
            });
        } catch (\Exception $e) {
            // Log and return user-friendly error
            \Log::error('Failed creating client and admin user', ['error' => $e->getMessage()]);
            return back()->withErrors(['server' => 'Failed to create client. Please try again or contact support.']);
        }

        return back()->with('success', 'Client and tenant created; initial admin user added.');
    }

    public function update(Request $request, Client $client)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'business_type'      => 'nullable|string|max:255',
            'status'             => 'required|in:active,inactive,suspended',
            'wa_base_url'        => 'nullable|url',
            'wa_api_key'         => 'nullable|string',
            'wa_session'         => 'nullable|string',
            'openrouter_api_key' => 'nullable|string',
            'openrouter_model'   => 'nullable|string',
            'ai_instruction'     => 'nullable|string',
        ]);

        // Don't overwrite keys if left blank (masked)
        if (empty($data['wa_api_key']))         unset($data['wa_api_key']);
        if (empty($data['openrouter_api_key'])) unset($data['openrouter_api_key']);

        $client->update($data);

        return back()->with('success', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $client->delete();
        return back()->with('success', 'Client removed.');
    }
}
