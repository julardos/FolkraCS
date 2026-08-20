<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\SupportTicket;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $clients = Client::latest()->get();

        return Inertia::render('Landlord/Dashboard', [
            'stats' => [
                'total_clients'        => $clients->count(),
                'active_clients'       => $clients->where('status', 'active')->count(),
                'suspended_clients'    => $clients->where('status', 'suspended')->count(),
                'total_customers'      => Customer::count(),
                'total_conversations'  => Conversation::count(),
                'active_conversations' => Conversation::where('status', 'active')->count(),
                'open_tickets'         => SupportTicket::where('status', 'open')->count(),
                'total_users'          => User::whereNotNull('tenant_id')->count(),
            ],
            'clients' => $clients->map(fn($c) => [
                'id'             => $c->id,
                'name'           => $c->name,
                'status'         => $c->status,
                'slug'           => $c->slug,
                'business_type'  => $c->business_type,
                'conversations'  => Conversation::where('client_id', $c->id)->count(),
                'open_tickets'   => SupportTicket::where('client_id', $c->id)->where('status', 'open')->count(),
                'domain'         => $c->slug ? $c->slug . '.' . env('TENANT_DOMAIN_SUFFIX', 'folkra.co') : null,
            ])->values(),
        ]);
    }
}
