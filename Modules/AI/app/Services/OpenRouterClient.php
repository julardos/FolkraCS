<?php

namespace Modules\AI\Services;

use App\Models\Client;
use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterClient
{
    private string $apiKey;
    private string $model;
    private string $baseUrl = 'https://openrouter.ai/api/v1';

    public function __construct(?Client $client = null)
    {
        $client = $client ?? Client::first();

        $this->apiKey = $client?->openrouter_api_key
            ?? Setting::get('ai.api_key')
            ?? config('services.openrouter.key')
            ?? env('LKHM_OR_API_KEY', '');

        $this->model = $client?->openrouter_model
            ?? Setting::get('ai.model')
            ?? config('services.openrouter.model')
            ?? env('LKHM_OR_MODEL', 'openai/gpt-4o-mini');
    }

    public function chat(string $systemPrompt, array $messages): string
    {
        $payload = [
            'model'    => $this->model,
            'messages' => array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $messages
            ),
        ];

        $response = Http::withToken($this->apiKey)
            ->withHeaders(['HTTP-Referer' => config('app.url'), 'X-Title' => 'FolkraCS'])
            ->timeout(60)
            ->post("{$this->baseUrl}/chat/completions", $payload);

        if (! $response->successful()) {
            throw new RuntimeException("OpenRouter error: {$response->status()} — {$response->body()}");
        }

        return $response->json('choices.0.message.content') ?? '';
    }
}
