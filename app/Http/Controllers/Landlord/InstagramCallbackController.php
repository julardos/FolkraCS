<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramCallbackController extends Controller
{
    public function __invoke(Request $request)
    {
        if ($request->has('error')) {
            return $this->failRedirect(null, $request->input('error_description', 'Authorization denied.'));
        }

        // Decrypt and validate state
        try {
            $state    = decrypt($request->input('state'));
            $clientId = $state['client_id'] ?? null;
            $ts       = $state['ts'] ?? 0;
        } catch (\Throwable) {
            return $this->failRedirect(null, 'Invalid state parameter.');
        }

        if (now()->timestamp - $ts > 600) {
            return $this->failRedirect(null, 'OAuth session expired. Please try again.');
        }

        $client = Client::find($clientId);
        if (! $client) {
            return $this->failRedirect(null, 'Client not found.');
        }

        $code        = $request->input('code');
        $appId       = config('services.meta.app_id');
        $appSecret   = config('services.meta.app_secret');
        $redirectUri = config('services.meta.redirect_uri');

        // Step 1 — Exchange code for short-lived token
        // Instagram Business Login uses api.instagram.com, NOT graph.facebook.com
        $tokenRes = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id'     => $appId,
            'client_secret' => $appSecret,
            'grant_type'    => 'authorization_code',
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);

        if (! $tokenRes->successful()) {
            Log::error('Instagram token exchange failed', [
                'client'   => $client->id,
                'status'   => $tokenRes->status(),
                'response' => $tokenRes->body(),
            ]);
            return $this->failRedirect($client->tenant_id, 'Token exchange failed. Please try again.');
        }

        $shortLivedToken = $tokenRes->json('access_token');

        // Step 2 — Exchange for long-lived token (~60 days)
        // Uses graph.instagram.com with ig_exchange_token grant
        $longRes   = Http::get('https://graph.instagram.com/access_token', [
            'grant_type'        => 'ig_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $appSecret,
            'access_token'      => $shortLivedToken,
        ]);

        $longToken = $longRes->successful() ? $longRes->json('access_token') : $shortLivedToken;
        $expiresIn = $longRes->successful() ? $longRes->json('expires_in', 5183944) : 5183944;

        // Step 3 — Get Instagram Business account info
        // With Instagram Business Login, /me returns user_id and username directly
        $meRes = Http::get('https://graph.instagram.com/me', [
            'fields'       => 'user_id,username',
            'access_token' => $longToken,
        ]);

        $igAccountId = null;
        $igUsername  = null;

        if ($meRes->successful()) {
            $igAccountId = $meRes->json('user_id');
            $igUsername  = $meRes->json('username');
        }

        $client->update([
            'instagram_access_token'     => $longToken,
            'instagram_account_id'       => $igAccountId,
            'instagram_username'         => $igUsername,
            'instagram_token_expires_at' => now()->addSeconds($expiresIn),
        ]);

        // Subscribe this IG account to receive webhook events.
        // Dashboard-level webhook setup only registers the URL globally.
        // This call tells Meta to actually send events for this specific account.
        $subRes = Http::post("https://graph.instagram.com/v21.0/{$igAccountId}/subscribed_apps", [
            'subscribed_fields' => 'messages',
            'access_token'      => $longToken,
        ]);

        Log::info('Instagram connected + webhook subscribed', [
            'client'           => $client->id,
            'ig_user_id'       => $igAccountId,
            'ig_username'      => $igUsername,
            'sub_status'       => $subRes->status(),
            'sub_response'     => $subRes->json(),
        ]);

        $subOk = $subRes->successful() && ($subRes->json('success') === true);

        $message = $igAccountId
            ? "Instagram @{$igUsername} berhasil terhubung." . ($subOk ? '' : ' (Webhook subscription gagal — coba disconnect & connect ulang.)')
            : 'Token tersimpan, tapi info akun tidak ditemukan. Coba disconnect dan connect ulang.';

        return $this->successRedirect($client, $message);
    }

    private function tenantConnectionsUrl(?string $tenantId): ?string
    {
        if (! $tenantId) return null;

        $suffix = env('TENANT_DOMAIN_SUFFIX', 'folkra.co');
        return "https://{$tenantId}.{$suffix}/connections";
    }

    private function successRedirect(Client $client, string $message): \Illuminate\Http\RedirectResponse
    {
        $url = $this->tenantConnectionsUrl($client->tenant_id) ?? '/';
        return redirect($url)->with('success', $message);
    }

    private function failRedirect(?string $tenantId, string $error): \Illuminate\Http\RedirectResponse
    {
        $url = $this->tenantConnectionsUrl($tenantId) ?? '/';
        return redirect($url)->withErrors(['instagram' => $error]);
    }
}
