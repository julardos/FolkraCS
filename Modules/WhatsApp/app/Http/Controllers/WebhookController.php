<?php

namespace Modules\WhatsApp\Http\Controllers;

use App\Models\Customer;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\AI\Jobs\ProcessMessageJob;
use Modules\WhatsApp\DTOs\IncomingMessage;

class WebhookController extends Controller
{
    public function receive(Request $request): Response
    {
        $msg = IncomingMessage::fromWebhook($request->input('body', $request->all()));

        if (! $msg->isProcessable()) {
            return response('', 200);
        }

        $customer = Customer::firstOrCreate(
            ['phone' => $msg->senderPhone],
            [
                'phone_lid'  => $msg->chatId,
                'push_name'  => $msg->pushName,
                'wa_session' => $msg->session,
            ]
        );

        // Update push name if it changed
        if ($msg->pushName && $customer->push_name !== $msg->pushName) {
            $customer->update(['push_name' => $msg->pushName]);
        }

        // Human takeover active — AI stays silent
        if ($customer->is_human_takeover) {
            return response('', 200);
        }

        ProcessMessageJob::dispatch($customer, $msg->chatId, $msg->body, $msg->session);

        return response('', 200);
    }
}
