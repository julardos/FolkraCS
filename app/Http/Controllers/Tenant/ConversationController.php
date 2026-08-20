<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Conversation;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index()
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);

        $conversations = Conversation::where('client_id', $client->id)
            ->with('customer:id,name,phone,push_name,is_human_takeover')
            ->withCount('messages')
            ->latest()
            ->paginate(30);

        return Inertia::render('Tenant/Conversations', [
            'conversations' => $conversations,
        ]);
    }

    public function show(Conversation $conversation)
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);
        abort_if($conversation->client_id !== $client->id, 403);

        return Inertia::render('Tenant/ConversationDetail', [
            'conversation' => $conversation->load('customer', 'messages'),
        ]);
    }
}
