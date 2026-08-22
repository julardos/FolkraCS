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
        // User denied
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

        // Reject if state is older than 10 minutes
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

        // 1. Exchange code → short-lived user access token
        $tokenRes = Http::post('https://graph.facebook.com/v20.0/oauth/access_token', [
            'client_id'     => $appId,
            'client_secret' => $appSecret,
            'redirect_uri'  => $redirectUri,
            'code'          => $code,
        ]);

        if (! $tokenRes->successful()) {
            Log::error('Instagram token exchange failed', ['client' => $client->id, 'response' => $tokenRes->json()]);
            return $this->failRedirect($client->tenant_id, 'Token exchange failed. Please try again.');
        }

        $shortLivedToken = $tokenRes->json('access_token');

        // 2. Exchange for long-lived token (~60 days)
        $longRes   = Http::get('https://graph.facebook.com/v20.0/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        $longToken = $longRes->successful() ? $longRes->json('access_token') : $shortLivedToken;
        $expiresIn = $longRes->successful() ? $longRes->json('expires_in', 5183944) : 5183944;

        // 3. Find Instagram Business account + Page Access Token
        // We need the PAGE access token (not user token) to send DMs via Graph API.
        // /me/accounts returns each page's own access_token alongside its IG account.
        $pagesRes = Http::get('https://graph.facebook.com/v20.0/me/accounts', [
            'access_token' => $longToken,
            'fields'       => 'access_token,instagram_business_account{id,username},name',
        ]);

        $igAccountId   = null;
        $igUsername    = null;
        $pageToken     = $longToken; // fallback to user token if no page token found
        $pageTokenExpiry = $expiresIn;

        if ($pagesRes->successful()) {
            foreach ($pagesRes->json('data', []) as $page) {
                $ig = $page['instagram_business_account'] ?? null;
                if ($ig) {
                    $igAccountId = $ig['id'];
                    $igUsername  = $ig['username'] ?? null;
                    // Page access tokens from /me/accounts are already long-lived
                    // when the user token is long-lived — they don't expire.
                    $pageToken       = $page['access_token'] ?? $longToken;
                    $pageTokenExpiry = isset($page['access_token']) ? 0 : $expiresIn; // 0 = never
                    break;
                }
            }
        }

        $client->update([
            'instagram_access_token'     => $pageToken,
            'instagram_account_id'       => $igAccountId,
            'instagram_username'         => $igUsername,
            // Page tokens don't expire when derived from a long-lived user token.
            // Store null expiry so we don't show a false warning in the UI.
            'instagram_token_expires_at' => $pageTokenExpiry > 0 ? now()->addSeconds($pageTokenExpiry) : null,
        ]);

        $message = $igUsername
            ? "Instagram connected as @{$igUsername}."
            : 'Token saved. No Instagram Business account found on your Facebook pages — check your Meta setup.';

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
