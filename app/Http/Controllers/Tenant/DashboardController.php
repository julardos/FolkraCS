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
        $client = Client::where('tenant_id', tenant('id'))->firstOrFail();

        return Inertia::render('Tenant/Dashboard', [
            'client'              => ['name' => $client->name, 'status' => $client->status],
            'stats' => [
                'conversations'   => Conversation::where('client_id', $client->id)->count(),
                'active'          => Conversation::where('client_id', $client->id)->where('status', 'active')->count(),
                'open_tickets'    => SupportTicket::where('client_id', $client->id)->where('status', 'open')->count(),
            ],
        ]);
    }
}
