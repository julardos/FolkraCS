<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\SupportTicket;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrCreate(
            ['tenant_id' => tenant('id')],
            ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']
        );

        return Inertia::render('Tenant/Dashboard', [
            'client' => ['name' => $client->name, 'status' => $client->status],
            'wa'     => [
                'session'    => $client->wa_session,
                'configured' => (bool) ($client->wa_base_url && $client->wa_session),
            ],
            'stats'  => [
                'conversations' => Conversation::where('client_id', $client->id)->count(),
                'active'        => Conversation::where('client_id', $client->id)->where('status', 'active')->count(),
                'open_tickets'  => SupportTicket::where('client_id', $client->id)->where('status', 'open')->count(),
            ],
        ]);
    }
}
