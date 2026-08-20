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
    }

    public function sendText(string $chatId, string $text, string $session): bool
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
                ->withoutVerifying()
                ->timeout(15)
                ->post("{$this->baseUrl}/api/sendText", [
                    'chatId'      => $chatId,
                    'text'        => $text,
                    'session'     => $session,
                    'linkPreview' => true,
                ]);

            return $response->successful();
        } catch (\Throwable $e) {
            Log::error('WahaClient::sendText failed', ['error' => $e->getMessage(), 'chatId' => $chatId]);
            return false;
        }
    }

    public function sendFile(string $chatId, string $url, string $filename, string $mimetype, string $caption, string $session): bool
    {
        try {
            $response = Http::withHeaders(['X-Api-Key' => $this->apiKey])
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
            Log::error('WahaClient::sendFile failed', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
