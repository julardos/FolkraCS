<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ConnectionsController extends Controller
{
    private function client(): Client
    {
        return Client::where('tenant_id', tenant('id'))->firstOrFail();
    }

    // ── Page ──────────────────────────────────────────────────────────────

    public function index()
    {
        $client = $this->client();

        return Inertia::render('Tenant/Connections', [
            'channels'    => $client->channels,
            'wa'          => [
                'session'    => $client->wa_session,
                'configured' => (bool) ($client->wa_base_url && $client->wa_session),
            ],
            'instagram'   => [
                'connected' => (bool) $client->instagram_account_id,
                'username'  => $client->instagram_username,
                'accountId' => $client->instagram_account_id,
                'expiresAt' => $client->instagram_token_expires_at?->toDateString(),
            ],
        ]);
    }

    // ── WhatsApp (WAHA proxy) ─────────────────────────────────────────────

    public function waStatus()
    {
        $client = $this->client();

        if (! $client->wa_base_url || ! $client->wa_session) {
            return response()->json(['status' => 'NOT_CONFIGURED'], 200);
        }

        try {
            $res = Http::withHeaders($this->waHeaders($client))
                ->timeout(8)
                ->get("{$client->wa_base_url}/api/sessions/{$client->wa_session}");

            return response()->json($res->json(), $res->status());
        } catch (\Throwable $e) {
            Log::warning('WAHA status check failed', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], 503);
        }
    }

    public function waQr()
    {
        $client = $this->client();

        abort_unless($client->wa_base_url && $client->wa_session, 422, 'WhatsApp not configured.');

        try {
            $res = Http::withHeaders($this->waHeaders($client))
                ->timeout(10)
                ->get("{$client->wa_base_url}/api/sessions/{$client->wa_session}/auth/qr");

            if (! $res->successful()) {
                return response()->json(['error' => 'QR not available'], 422);
            }

            // WAHA returns { mime: 'image/png', data: 'base64...' }
            $body = $res->json();
            return response()->json([
                'mime' => $body['mime'] ?? 'image/png',
                'data' => $body['data'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    public function waStart()
    {
        $client = $this->client();

        abort_unless($client->wa_base_url && $client->wa_session, 422, 'WhatsApp not configured.');

        try {
            $res = Http::withHeaders($this->waHeaders($client))
                ->timeout(10)
                ->post("{$client->wa_base_url}/api/sessions/{$client->wa_session}/start");

            return response()->json($res->json(), $res->status());
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 503);
        }
    }

    private function waHeaders(Client $client): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if ($client->wa_api_key) {
            $headers['X-Api-Key'] = $client->wa_api_key;
        }
        return $headers;
    }

    // ── Instagram OAuth ───────────────────────────────────────────────────

    public function instagramConnect(Request $request)
    {
        $appId       = config('services.meta.app_id');
        $redirectUri = config('services.meta.redirect_uri');

        $params = http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'instagram_manage_messages,instagram_basic,pages_show_list,pages_messaging',
            'response_type' => 'code',
            'state'         => csrf_token(),
        ]);

        return redirect("https://www.facebook.com/v20.0/dialog/oauth?{$params}");
    }

    public function instagramCallback(Request $request)
    {
        if ($request->has('error')) {
            return redirect('/connections')->withErrors(['instagram' => $request->input('error_description', 'Authorization denied.')]);
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
            Log::error('Instagram token exchange failed', $tokenRes->json());
            return redirect('/connections')->withErrors(['instagram' => 'Token exchange failed.']);
        }

        $shortLivedToken = $tokenRes->json('access_token');

        // 2. Exchange for long-lived token (60 days)
        $longRes = Http::get('https://graph.facebook.com/v20.0/oauth/access_token', [
            'grant_type'        => 'fb_exchange_token',
            'client_id'         => $appId,
            'client_secret'     => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        $longToken   = $longRes->successful() ? $longRes->json('access_token') : $shortLivedToken;
        $expiresIn   = $longRes->successful() ? $longRes->json('expires_in', 5183944) : 5183944;

        // 3. Get Instagram accounts via connected Facebook pages
        $pagesRes = Http::get('https://graph.facebook.com/v20.0/me/accounts', [
            'access_token' => $longToken,
            'fields'       => 'instagram_business_account{id,username},name',
        ]);

        $igAccountId = null;
        $igUsername  = null;

        if ($pagesRes->successful()) {
            foreach ($pagesRes->json('data', []) as $page) {
                $ig = $page['instagram_business_account'] ?? null;
                if ($ig) {
                    $igAccountId = $ig['id'];
                    $igUsername  = $ig['username'] ?? null;
                    break;
                }
            }
        }

        $this->client()->update([
            'instagram_access_token'    => $longToken,
            'instagram_account_id'      => $igAccountId,
            'instagram_username'        => $igUsername,
            'instagram_token_expires_at'=> now()->addSeconds($expiresIn),
        ]);

        return redirect('/connections')->with('success', $igUsername
            ? "Instagram connected as @{$igUsername}."
            : 'Instagram token saved. No Instagram Business account found on your Facebook pages — check your Meta setup.'
        );
    }

    public function instagramDisconnect()
    {
        $this->client()->update([
            'instagram_access_token'     => null,
            'instagram_account_id'       => null,
            'instagram_username'         => null,
            'instagram_token_expires_at' => null,
        ]);

        return back()->with('success', 'Instagram disconnected.');
    }
}
