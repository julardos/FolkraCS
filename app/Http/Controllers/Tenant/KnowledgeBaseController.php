<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KnowledgeBaseController extends Controller
{
    private function client(): Client
    {
        return Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);
    }

    public function index()
    {
        $client = $this->client();

        return Inertia::render('Tenant/KnowledgeBase', [
            'entries' => KnowledgeBase::where('client_id', $client->id)
                ->orderBy('sort_order')->orderBy('id')
                ->get(['id', 'title', 'content', 'is_active', 'sort_order']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'   => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $client = $this->client();

        KnowledgeBase::create([...$data, 'client_id' => $client->id, 'is_active' => true]);

        return back()->with('success', 'Entry added.');
    }

    public function update(Request $request, KnowledgeBase $kb)
    {
        $this->authorizeKb($kb);

        $data = $request->validate([
            'title'     => 'required|string|max:255',
            'content'   => 'required|string',
            'is_active' => 'boolean',
        ]);

        $kb->update($data);

        return back()->with('success', 'Entry updated.');
    }

    public function destroy(KnowledgeBase $kb)
    {
        $this->authorizeKb($kb);
        $kb->delete();
        return back()->with('success', 'Entry deleted.');
    }

    private function authorizeKb(KnowledgeBase $kb): void
    {
        $client = $this->client();
        abort_if($kb->client_id !== $client->id, 403);
    }
}
