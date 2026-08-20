<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Message;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::set('wa.base_url', 'https://wa.test');
        Setting::set('wa.api_key', 'test_wa_key');
        Setting::set('wa.session', 'test_session');
        Setting::set('ai.api_key', 'test_or_key');
        Setting::set('ai.model', 'openai/gpt-4o-mini');
        Setting::set('ai.system_prompt', 'You are an AI support assistant.');
    }

    public function test_receives_personal_message_and_responds_via_openrouter_and_waha(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Halo! Ada yang bisa kami bantu mengenai bantuan hukum?',
                        ],
                    ],
                ],
            ], 200),

            'https://wa.test/api/sendText' => Http::response(['status' => 'success'], 200),
        ]);

        $payload = [
            'event'   => 'message',
            'session' => 'test_session',
            'payload' => [
                'id'       => 'msg_12345',
                'from'     => '6281234567890@c.us',
                'to'       => 'test_session@c.us',
                'body'     => 'Halo mas, mau tanya konsultasi hukum',
                'fromMe'   => false,
                'pushName' => 'Budi',
            ],
        ];

        $response = $this->postJson('/api/webhook', $payload);

        $response->assertStatus(200);

        // Verify Customer created
        $this->assertDatabaseHas('customers', [
            'phone'     => '6281234567890',
            'push_name' => 'Budi',
        ]);

        // Verify Messages saved in DB
        $customer = Customer::where('phone', '6281234567890')->first();
        $this->assertNotNull($customer);
        $this->assertNotNull($customer->activeConversation);

        $messages = Message::where('conversation_id', $customer->activeConversation->id)->get();
        $this->assertCount(2, $messages);
        $this->assertEquals('user', $messages[0]->role);
        $this->assertEquals('Halo mas, mau tanya konsultasi hukum', $messages[0]->content);
        $this->assertEquals('assistant', $messages[1]->role);
        $this->assertEquals('Halo! Ada yang bisa kami bantu mengenai bantuan hukum?', $messages[1]->content);

        // Verify OpenRouter HTTP call
        Http::assertSent(function ($request) {
            return $request->url() === 'https://openrouter.ai/api/v1/chat/completions'
                && $request->hasHeader('Authorization', 'Bearer test_or_key');
        });

        // Verify WAHA sendText HTTP call
        Http::assertSent(function ($request) {
            return $request->url() === 'https://wa.test/api/sendText'
                && $request['chatId'] === '6281234567890@c.us'
                && $request['text'] === 'Halo! Ada yang bisa kami bantu mengenai bantuan hukum?';
        });
    }

    public function test_ignores_group_messages(): void
    {
        Http::fake();

        $payload = [
            'event'   => 'message',
            'session' => 'test_session',
            'payload' => [
                'from'   => '123456789@g.us',
                'body'   => 'Pesan di grup',
                'fromMe' => false,
            ],
        ];

        $response = $this->postJson('/api/webhook', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseCount('customers', 0);
        Http::assertNothingSent();
    }

    public function test_ignores_self_sent_messages(): void
    {
        Http::fake();

        $payload = [
            'event'   => 'message',
            'session' => 'test_session',
            'payload' => [
                'from'   => '6281234567890@c.us',
                'body'   => 'Balasan dari saya sendiri',
                'fromMe' => true,
            ],
        ];

        $response = $this->postJson('/api/webhook', $payload);
        $response->assertStatus(200);

        $this->assertDatabaseCount('customers', 0);
        Http::assertNothingSent();
    }

    public function test_human_takeover_prevents_ai_auto_response(): void
    {
        Http::fake();

        $customer = Customer::create([
            'phone'             => '6289999999999',
            'phone_lid'         => '6289999999999@c.us',
            'push_name'         => 'Siti',
            'is_human_takeover' => true,
        ]);

        $payload = [
            'event'   => 'message',
            'session' => 'test_session',
            'payload' => [
                'from'     => '6289999999999@c.us',
                'body'     => 'Apakah ada admin manusia?',
                'fromMe'   => false,
                'pushName' => 'Siti',
            ],
        ];

        $response = $this->postJson('/api/webhook', $payload);
        $response->assertStatus(200);

        // AI shouldn't send anything
        Http::assertNothingSent();
    }

    public function test_uses_client_db_model_credentials_first(): void
    {
        \App\Models\Client::create([
            'name'               => 'Test Client DB',
            'business_type'      => 'General',
            'status'             => 'active',
            'wa_base_url'        => 'https://client-wa.test',
            'wa_api_key'         => 'client_wa_key_123',
            'wa_session'         => 'client_session',
            'openrouter_api_key' => 'client_or_key_456',
            'openrouter_model'   => 'client/model-789',
            'ai_instruction'     => 'System prompt from Client DB',
        ]);

        $waha = new \Modules\WhatsApp\Services\WahaClient();
        $ai   = new \Modules\AI\Services\OpenRouterClient();
        $promptBuilder = new \Modules\AI\Services\PromptBuilder();

        // Inspect via reflection
        $refWahaKey = (new \ReflectionClass($waha))->getProperty('apiKey');
        $refWahaUrl = (new \ReflectionClass($waha))->getProperty('baseUrl');
        $refAiKey   = (new \ReflectionClass($ai))->getProperty('apiKey');
        $refAiModel = (new \ReflectionClass($ai))->getProperty('model');

        $this->assertEquals('client_wa_key_123', $refWahaKey->getValue($waha));
        $this->assertEquals('https://client-wa.test', $refWahaUrl->getValue($waha));
        $this->assertEquals('client_or_key_456', $refAiKey->getValue($ai));
        $this->assertEquals('client/model-789', $refAiModel->getValue($ai));
        $this->assertStringContainsString('System prompt from Client DB', $promptBuilder->build());
    }

    public function test_conversation_memory_injects_rolling_summary(): void
    {
        $customer = Customer::create(['phone' => '6281111111111']);
        $conversation = \App\Models\Conversation::create([
            'customer_id' => $customer->id,
            'wa_session'  => 'test_session',
            'status'      => 'active',
            'summary'     => 'Klien berkonsultasi mengenai sengketa tanah warisan.',
        ]);

        $conversation->messages()->create(['role' => 'user', 'content' => 'Bagaimana syaratnya?']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Perlu bawa KTP dan surat tanah.']);

        $memory = new \Modules\AI\Services\ConversationMemory();
        $history = $memory->load($conversation, limit: 6);

        $this->assertCount(3, $history);
        $this->assertEquals('system', $history[0]['role']);
        $this->assertStringContainsString('Klien berkonsultasi mengenai sengketa tanah warisan.', $history[0]['content']);
        $this->assertEquals('Bagaimana syaratnya?', $history[1]['content']);
        $this->assertEquals('Perlu bawa KTP dan surat tanah.', $history[2]['content']);
    }

    public function test_conversation_memory_summarizes_when_threshold_reached(): void
    {
        Http::fake([
            'https://openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'Ringkasan: Klien menanyakan konsultasi sengketa kerja dan telah dijelaskan persyaratannya.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $customer = Customer::create(['phone' => '6282222222222']);
        $conversation = \App\Models\Conversation::create([
            'customer_id' => $customer->id,
            'wa_session'  => 'test_session',
            'status'      => 'active',
        ]);

        // Create 10 messages (exceeding threshold of 8)
        for ($i = 1; $i <= 5; $i++) {
            $conversation->messages()->create(['role' => 'user', 'content' => "Pertanyaan {$i}"]);
            $conversation->messages()->create(['role' => 'assistant', 'content' => "Jawaban {$i}"]);
        }

        $memory = new \Modules\AI\Services\ConversationMemory();
        $ai = new \Modules\AI\Services\OpenRouterClient();

        $memory->summarizeIfNeeded($conversation, $ai, threshold: 8, keepRecent: 6);

        $conversation->refresh();
        $this->assertNotEmpty($conversation->summary);
        $this->assertStringContainsString('Ringkasan: Klien menanyakan konsultasi', $conversation->summary);
    }
}
