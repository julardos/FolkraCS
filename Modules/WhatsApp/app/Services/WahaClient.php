<?php

namespace Modules\WhatsApp\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(Setting::get('wa.base_url', ''), '/');
        $this->apiKey  = Setting::get('wa.api_key', '');
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
