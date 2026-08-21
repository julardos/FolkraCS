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

    public function instagramConnect()
    {
        // Encode tenant ID in state so the central callback knows which client to update.
        // encrypt() signs + encrypts, so it can't be tampered with.
        $state = encrypt(['tenant_id' => tenant('id'), 'ts' => now()->timestamp]);

        $params = http_build_query([
            'client_id'     => config('services.meta.app_id'),
            'redirect_uri'  => config('services.meta.redirect_uri'),
            'scope'         => 'instagram_manage_messages,instagram_basic,pages_show_list,pages_messaging',
            'response_type' => 'code',
            'state'         => $state,
        ]);

        return redirect("https://www.facebook.com/v20.0/dialog/oauth?{$params}");
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
