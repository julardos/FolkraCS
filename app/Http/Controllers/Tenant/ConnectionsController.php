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

        Log::info('[DEV] WA status check', [
            'tenant'     => tenant('id'),
            'wa_base_url'=> $client->wa_base_url,
            'wa_session' => $client->wa_session,
            'api_key'    => $client->wa_api_key ? $client->wa_api_key : 'NOT SET',
        ]);

        $waha = new \Modules\WhatsApp\Services\WahaClient($client);
        $status = $waha->getSessionStatus();

        $httpCode = match ($status['status']) {
            'ERROR' => $status['http_code'] ?? 503,
            'AUTH_ERROR' => 403,
            default => 200,
        };

        return response()->json($status, $httpCode);
    }

    public function waQr()
    {
        $waha = new \Modules\WhatsApp\Services\WahaClient($this->client());
        $res = $waha->getQrCode();

        if (isset($res['error'])) {
            return response()->json($res, 422);
        }

        return response()->json($res, 200);
    }

    public function waStart()
    {
        $waha = new \Modules\WhatsApp\Services\WahaClient($this->client());
        $res = $waha->startSession();

        $httpCode = isset($res['http_code']) ? $res['http_code'] : 200;
        return response()->json($res, $httpCode);
    }

    // ── Instagram OAuth ───────────────────────────────────────────────────

    public function instagramConnect()
    {
        $appId       = config('services.meta.app_id');
        $appSecret   = config('services.meta.app_secret');
        $redirectUri = config('services.meta.redirect_uri');
        $client      = $this->client();

        Log::info('[DEV] Instagram connect initiated', [
            'client_id'    => $client->id,
            'tenant_id'    => $client->tenant_id,
            'app_id'       => $appId ?? 'NOT SET',
            'app_secret'   => $appSecret ? '****' . substr($appSecret, -4) : 'NOT SET',
            'redirect_uri' => $redirectUri ?? 'NOT SET',
        ]);

        // Use client DB id (reliable integer) instead of tenant('id') which can return 0
        $state = encrypt(['client_id' => $client->id, 'ts' => now()->timestamp]);

        $params = http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => $redirectUri,
            'response_type' => 'code',
            'scope'         => 'instagram_business_basic,instagram_business_manage_messages,instagram_business_manage_comments,instagram_business_content_publish',
            'state'         => $state,
        ]);

        return redirect("https://www.instagram.com/oauth/authorize?{$params}");
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
