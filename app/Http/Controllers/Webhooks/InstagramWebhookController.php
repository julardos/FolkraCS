<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    // Meta calls this GET to verify the endpoint when you save the URL in the dashboard
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('services.meta.webhook_verify_token')) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    // Meta POSTs events here
    public function receive(Request $request)
    {
        // Validate the request came from Meta using the app secret
        if (! $this->isValidSignature($request)) {
            Log::warning('Instagram webhook: invalid signature');
            return response('Forbidden', 403);
        }

        $payload = $request->json()->all();

        if (($payload['object'] ?? '') !== 'instagram') {
            return response('ok', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $igAccountId = $entry['id'] ?? null;

            $client = Client::where('instagram_account_id', $igAccountId)->first();

            if (! $client) {
                Log::info('Instagram webhook: no client for account', ['ig_id' => $igAccountId]);
                continue;
            }

            foreach ($entry['messaging'] ?? [] as $event) {
                $this->handleMessagingEvent($client, $event);
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function handleMessagingEvent(Client $client, array $event): void
    {
        $senderId = $event['sender']['id'] ?? null;
        $message  = $event['message'] ?? null;

        if (! $senderId || ! $message || isset($message['is_echo'])) {
            return;
        }

        $text = $message['text'] ?? null;

        if (! $text) {
            // Attachments, stickers, etc. — ignore for now
            return;
        }

        Log::info('Instagram DM received', [
            'client'   => $client->name,
            'sender'   => $senderId,
            'message'  => $text,
        ]);

        // TODO: dispatch ProcessInstagramMessageJob($client, $senderId, $text)
        // This will mirror ProcessMessageJob but for Instagram DMs
    }

    private function isValidSignature(Request $request): bool
    {
        $signature = $request->header('X-Hub-Signature-256');

        if (! $signature) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac(
            'sha256',
            $request->getContent(),
            config('services.meta.app_secret')
        );

        return hash_equals($expected, $signature);
    }
}
