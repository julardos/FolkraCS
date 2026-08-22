<?php

namespace App\Services;

use App\Models\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramClient
{
    private string $baseUrl = 'https://graph.instagram.com/v21.0';

    public function sendText(string $recipientId, string $text, Client $client): void
    {
        if (! $client->instagram_account_id || ! $client->instagram_access_token) {
            Log::warning('InstagramClient: missing account ID or token', ['client' => $client->id]);
            return;
        }

        // Instagram Business Login: send via graph.instagram.com/{ig-user-id}/messages
        $res = Http::withToken($client->instagram_access_token)
            ->post("{$this->baseUrl}/{$client->instagram_account_id}/messages", [
                'recipient' => ['id' => $recipientId],
                'message'   => ['text' => $text],
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
