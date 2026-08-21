<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramClient
{
    private string $baseUrl = 'https://graph.facebook.com/v20.0';

    public function sendText(string $recipientId, string $text, Client $client): void
    {
        if (! $client->instagram_account_id || ! $client->instagram_access_token) {
            Log::warning('InstagramClient: missing account ID or token', ['client' => $client->id]);
            return;
        }

        $res = Http::post("{$this->baseUrl}/{$client->instagram_account_id}/messages", [
            'recipient'    => ['id' => $recipientId],
            'message'      => ['text' => $text],
            'access_token' => $client->instagram_access_token,
        ]);

        if (! $res->successful()) {
            Log::error('InstagramClient: send failed', [
                'status'   => $res->status(),
                'response' => $res->json(),
                'client'   => $client->id,
            ]);
        }
    }
}
