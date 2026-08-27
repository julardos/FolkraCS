<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Modules\AI\Services\OpenRouterClient;

class AiSettingsController extends Controller
{
    public function index()
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);

        $models = Cache::remember('openrouter_models', 3600, function () use ($client) {
            return (new OpenRouterClient($client))->getModels();
        });

        return Inertia::render('Tenant/AiSettings', [
            'client' => [
                'id'               => $client->id,
                'openrouter_model' => $client->openrouter_model,
                'ai_instruction'   => $client->ai_instruction,
                'masked_ai_key'    => $client->masked_ai_key,
            ],
            'models' => $models,
        ]);
    }

    public function update(Request $request)
    {
        $client = Client::where('tenant_id', tenant('id'))->firstOrCreate(['tenant_id' => tenant('id')], ['name' => tenant('id'), 'slug' => tenant('id'), 'status' => 'active', 'openrouter_model' => 'openai/gpt-4o-mini']);

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
