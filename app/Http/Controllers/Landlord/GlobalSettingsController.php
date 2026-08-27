<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Modules\AI\Services\OpenRouterClient;

class GlobalSettingsController extends Controller
{
    public function index()
    {
        $models = [];

        if (Setting::get('ai.api_key') || env('LKHM_OR_API_KEY')) {
            $models = Cache::remember('openrouter_models', 3600, fn() =>
                (new OpenRouterClient())->getModels()
            );
        }

        return Inertia::render('Landlord/Settings', [
            'settings' => [
                'wa_base_url'      => Setting::get('wa.base_url', ''),
                'ai_api_key'       => Setting::get('ai.api_key', ''),
                'ai_model'         => Setting::get('ai.model', 'openai/gpt-4o-mini'),
                'ai_system_prompt' => Setting::get('ai.system_prompt', ''),
                'timezone'         => Setting::get('timezone', 'Asia/Makassar'),
            ],
            'models' => $models,
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'wa_base_url'      => 'nullable|url',
            'ai_api_key'       => 'nullable|string',
            'ai_model'         => 'required|string',
            'ai_system_prompt' => 'nullable|string',
            'timezone'         => 'required|string',
        ]);

        Setting::set('wa.base_url',      $data['wa_base_url'] ?? '');
        Setting::set('ai.model',         $data['ai_model']);
        Setting::set('ai.system_prompt', $data['ai_system_prompt'] ?? '');
        Setting::set('timezone',         $data['timezone']);

        // Only overwrite API key if explicitly provided
        if (!empty($data['ai_api_key'])) {
            Setting::set('ai.api_key', $data['ai_api_key']);
            Cache::forget('openrouter_models');
        }

        return back()->with('success', 'Pengaturan global disimpan.');
    }
}
