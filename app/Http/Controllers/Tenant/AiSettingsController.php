<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AiSettingsController extends Controller
{
    public function index()
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrFail();

        return Inertia::render('Tenant/AiSettings', [
            'client' => [
                'id'                 => $client->id,
                'openrouter_model'   => $client->openrouter_model,
                'ai_instruction'     => $client->ai_instruction,
                'masked_ai_key'      => $client->masked_ai_key,
            ],
            'models' => [
                'openai/gpt-4o-mini', 'openai/gpt-4o',
                'anthropic/claude-3-haiku', 'anthropic/claude-sonnet-4-5',
                'google/gemini-flash-1.5', 'meta-llama/llama-3.1-8b-instruct',
            ],
        ]);
    }

    public function update(Request $request)
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrFail();

        $data = $request->validate([
            'openrouter_model'   => 'required|string',
            'openrouter_api_key' => 'nullable|string',
            'ai_instruction'     => 'nullable|string',
        ]);

        if (empty($data['openrouter_api_key'])) {
            unset($data['openrouter_api_key']);
        }

        $client->update($data);

        return back()->with('success', 'AI settings saved.');
    }
}
