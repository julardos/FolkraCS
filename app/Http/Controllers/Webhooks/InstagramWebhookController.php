<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInstagramMessageJob;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
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

    public function receive(Request $request)
    {
        $rawBody = $request->getContent();
        $payload = json_decode($rawBody, true) ?? [];

        // [DEV] Log everything arriving so we can trace the issue
        Log::info('[IG-WEBHOOK] Incoming', [
            'object'    => $payload['object'] ?? 'missing',
            'has_sig'   => $request->hasHeader('X-Hub-Signature-256'),
            'sig_valid' => $this->isValidSignature($request, $rawBody),
            'entries'   => count($payload['entry'] ?? []),
            'payload'   => $payload,
        ]);

        // TEMP: bypass signature for debugging — re-enable after confirming DMs arrive
        if (! config('app.debug') && ! $this->isValidSignature($request, $rawBody)) {
            Log::warning('[IG-WEBHOOK] Invalid signature — check META_SECRET on server');
            return response('Forbidden', 403);
        }

        $object = $payload['object'] ?? '';

        // Instagram Business Login: object = "instagram"
        // Messenger Platform (older IG Graph API): object = "page"
        // Accept both to be safe — log what we actually receive
        if (! in_array($object, ['instagram', 'page', 'instagram_business_account'])) {
            Log::info('[IG-WEBHOOK] Ignored — unrecognised object type', ['object' => $object]);
            return response('ok', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $igAccountId = $entry['id'] ?? null;

            Log::info('[IG-WEBHOOK] Processing entry', [
                'entry_id'    => $igAccountId,
                'has_messaging' => isset($entry['messaging']),
                'has_messages'  => isset($entry['messages']),
                'entry_keys'    => array_keys($entry),
            ]);

            $client = Client::where('instagram_account_id', $igAccountId)->first();

            if (! $client) {
                Log::warning('[IG-WEBHOOK] No client matched account ID', [
                    'ig_id'          => $igAccountId,
                    'stored_ig_ids'  => Client::whereNotNull('instagram_account_id')
                        ->pluck('instagram_account_id', 'id'),
                ]);
                continue;
            }

            // Instagram Business Login uses 'messaging' key (same as Messenger)
            $events = $entry['messaging'] ?? $entry['messages'] ?? [];

            foreach ($events as $event) {
                $this->handleMessagingEvent($client, $event);
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function handleMessagingEvent(Client $client, array $event): void
    {
        $senderId = $event['sender']['id'] ?? null;
        $message  = $event['message'] ?? null;

        Log::info('[IG-WEBHOOK] Message event', [
            'sender'   => $senderId,
            'is_echo'  => isset($message['is_echo']),
            'has_text' => isset($message['text']),
            'client'   => $client->id,
        ]);

        if (! $senderId || ! $message || isset($message['is_echo'])) {
            return;
        }

        $text = $message['text'] ?? null;

        if (! $text) {
            Log::info('[IG-WEBHOOK] Skipped — no text (attachment or sticker)');
            return;
        }

        Log::info('[IG-WEBHOOK] Dispatching job', [
            'client' => $client->id,
            'sender' => $senderId,
            'text'   => substr($text, 0, 100),
        ]);

        ProcessInstagramMessageJob::dispatch($client, $senderId, $text);
    }

    private function isValidSignature(Request $request, string $rawBody): bool
    {
        $signature = $request->header('X-Hub-Signature-256');
        $secret    = config('services.meta.app_secret');
        $expected  = $secret ? 'sha256=' . hash_hmac('sha256', $rawBody, $secret) : null;

        Log::debug('[IG-WEBHOOK] Signature check', [
            'secret_set'       => ! empty($secret),
            'secret_last4'     => $secret ? '****' . substr($secret, -4) : 'NOT SET',
            'received_sig'     => $signature ? substr($signature, 0, 20) . '...' : 'NONE',
            'expected_sig'     => $expected ? substr($expected, 0, 20) . '...' : 'N/A',
            'match'            => $expected && $signature && hash_equals($expected, $signature),
        ]);

        if (! $signature || ! $secret) {
            return false;
        }

        return hash_equals($expected, $signature);
    }
}
