<?php

namespace Modules\WhatsApp\Services;

use App\Models\Client;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaClient
{
    private string $baseUrl;
    private string $apiKey;
    private string $session;

    public function __construct(?Client $client = null)
    {
        $client = $client ?? Client::first();

        $url = $client?->wa_base_url
            ?? Setting::get('wa.base_url')
            ?? env('LKHM_WA_BASE_URL', '');

        $this->baseUrl = rtrim((string) $url, '/');

        $this->apiKey = $client?->wa_api_key
            ?? Setting::get('wa.api_key')
            ?? env('LKHM_WA_API_KEY', '');

        $this->session = $client?->wa_session
            ?? Setting::get('wa.session')
            ?? env('LKHM_WA_SESSION', 'default');
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getSessionName(): string
    {
        return $this->session;
    }

    public function isConfigured(): bool
    {
        return ! empty($this->baseUrl) && ! empty($this->session);
    }

    public function getSessionStatus(?string $session = null): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'NOT_CONFIGURED', 'message' => 'WhatsApp server or session is not configured.'];
        }

        $sessionName = ! empty($session) ? $session : $this->session;

        try {
            $response = Http::withHeaders($this->headers())
                ->withoutVerifying()
                ->timeout(8)
                ->get("{$this->baseUrl}/api/sessions/{$sessionName}");

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'status'   => $data['status'] ?? 'UNKNOWN',
                    'session'  => $sessionName,
                    'me'       => $data['me'] ?? null,
                    'details'  => $data,
                ];
            }

            if ($response->status() === 404) {
                return [
                    'status'   => 'STOPPED',
                    'session'  => $sessionName,
                    'message'  => 'Session is stopped or not found in WAHA.',
                ];
            }

            if ($response->status() === 401 || $response->status() === 403) {
                return [
                    'status'   => 'AUTH_ERROR',
                    'session'  => $sessionName,
                    'message'  => 'Invalid WAHA API key or unauthorized access to session.',
                ];
            }

            return [
                'status'   => 'ERROR',
                'session'  => $sessionName,
                'http_code'=> $response->status(),
                'message'  => $response->json('message') ?? $response->body() ?? 'Failed to get session status',
            ];
        } catch (\Throwable $e) {

            return [
                'status'   => 'ERROR',
                'session'  => $sessionName,
                'message'  => 'Cannot connect to WAHA server: ' . $e->getMessage(),
            ];
        }
    }

    public function getQrCode(?string $session = null): array
    {
        if (! $this->isConfigured()) {
            return ['error' => 'WhatsApp not configured'];
        }

        $sessionName = ! empty($session) ? $session : $this->session;

        try {
            // ?format=image returns binary PNG directly — more reliable than
            // the JSON endpoint whose `data` field varies by WAHA version.
            $response = Http::withHeaders($this->headers())
                ->withoutVerifying()
                ->timeout(10)
                ->get("{$this->baseUrl}/api/{$sessionName}/auth/qr", ['format' => 'image']);

            if ($response->successful()) {
                return [
                    'mime' => 'image/png',
                    'data' => base64_encode($response->body()),
                ];
            }

            return ['error' => 'QR not available (' . $response->status() . ')'];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function startSession(?string $session = null): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'NOT_CONFIGURED', 'message' => 'WhatsApp not configured'];
        }

        $sessionName = ! empty($session) ? $session : $this->session;

        try {
            $response = Http::withHeaders($this->headers())
                ->withoutVerifying()
                ->timeout(12)
                ->post("{$this->baseUrl}/api/sessions/{$sessionName}/start");

            if ($response->successful()) {
                return $response->json();
            }

            return [
                'status'   => 'ERROR',
                'http_code'=> $response->status(),
                'message'  => $response->json('message') ?? 'Failed to start session',
            ];
        } catch (\Throwable $e) {
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    public function restartSession(?string $session = null): array
    {
        if (! $this->isConfigured()) {
            return ['status' => 'NOT_CONFIGURED', 'message' => 'WhatsApp not configured'];
        }

        $sessionName = ! empty($session) ? $session : $this->session;

        try {
            $response = Http::withHeaders($this->headers())
                ->withoutVerifying()
                ->timeout(12)
                ->post("{$this->baseUrl}/api/sessions/{$sessionName}/restart");

            if ($response->successful()) {
                return $response->json() ?: ['status' => 'RESTARTING'];
            }

            return [
                'status'    => 'ERROR',
                'http_code' => $response->status(),
                'message'   => $response->json('message') ?? 'Failed to restart session',
            ];
        } catch (\Throwable $e) {
            return ['status' => 'ERROR', 'message' => $e->getMessage()];
        }
    }

    public function sendText(string $chatId, string $text, ?string $session = null): bool
    {
        $sessionName = ! empty($session) ? $session : $this->session;

        try {
            $url = "{$this->baseUrl}/api/sendText";

            $response = Http::withHeaders($this->headers())
                ->withoutVerifying()
                ->timeout(15)
                ->post($url, [
                    'chatId'      => $chatId,
                    'text'        => $text,
                    'session'     => $sessionName,
                    'linkPreview' => true,
                ]);

            if ($response->successful()) {
                return true;
            }

            // Log removed: sendText HTTP error handling
            // (error details are no longer logged)


            return false;
        } catch (\Throwable $e) {
            // Log removed: sendText exception handling
            return false;
        }
    }

    public function sendFile(string $chatId, string $url, string $filename, string $mimetype, string $caption, string $session): bool
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->withoutVerifying()
                ->timeout(30)
                ->post("{$this->baseUrl}/api/sendFile", [
                    'chatId'  => $chatId,
                    'file'    => compact('url', 'filename', 'mimetype'),
                    'caption' => $caption,
                    'session' => $session,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
                        // Log removed: sendFile error handling
            return false;
        }
    }

    private function headers(): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if (! empty($this->apiKey)) {
            $headers['X-Api-Key'] = $this->apiKey;
        }
        return $headers;
    }
}
