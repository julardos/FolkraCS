<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $clientId = $request->query('client_id');

        $query = Conversation::with('customer:id,name,phone,push_name,is_human_takeover')
            ->withCount('messages')
            ->latest();

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        return Inertia::render('Landlord/Conversations', [
            'conversations' => $query->paginate(40)->withQueryString(),
            'clients'       => Client::orderBy('name')->get(['id', 'name', 'slug']),
            'filters'       => ['client_id' => $clientId],
        ]);
    }
}
