<?php

namespace App\Jobs;

use App\Models\Client;
use App\Models\Conversation;
use App\Models\Customer;
use App\Services\InstagramClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\AI\Services\ConversationMemory;
use Modules\AI\Services\OpenRouterClient;
use Modules\AI\Services\OutputParser;
use Modules\AI\Services\PromptBuilder;

class ProcessInstagramMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries   = 2;
    public int $timeout = 90;

    public function __construct(
        public readonly Client $client,
        public readonly string $senderId,
        public readonly string $userMessage,
    ) {}

    public function handle(
        PromptBuilder     $promptBuilder,
        ConversationMemory $memory,
        OutputParser       $parser,
        InstagramClient    $instagram,
    ): void {
        Log::info('[IG-JOB] Started', [
            'client' => $this->client->id,
            'sender' => $this->senderId,
            'text'   => substr($this->userMessage, 0, 80),
        ]);

        $customer = $this->resolveCustomer();
        if ($customer->is_human_takeover) {
            return;
        }

        // Get or create active conversation
        $conversation = $customer->activeConversation ?? Conversation::create([
            'customer_id' => $customer->id,
            'wa_session'  => 'instagram',
            'status'      => 'active',
        ]);

        // Save incoming message
        $memory->append($conversation, 'user', $this->userMessage);

        // Build system prompt with this client's instruction + KB
        $systemPrompt = $promptBuilder->build($this->client);
        $history      = $memory->load($conversation);

        // Call AI with this client's API key and model
        $ai = new OpenRouterClient($this->client);

        try {
            $rawOutput = $ai->chat($systemPrompt, $history);
        } catch (\Throwable $e) {
            Log::error('ProcessInstagramMessageJob: AI call failed', [
                'error'  => $e->getMessage(),
                'client' => $this->client->id,
                'sender' => $this->senderId,
            ]);
            return;
        }

        // Parse output (booking/kendala markers work the same)
        $parsed = $parser->parse($rawOutput, $this->senderId, $this->senderId, null);

        // Save assistant reply
        $memory->append($conversation, 'assistant', $parsed->humanMessage, $rawOutput);

        // Send reply via Instagram DM
        if (! empty($parsed->humanMessage)) {
            $instagram->sendText($this->senderId, $parsed->humanMessage, $this->client);
        }

        // Rolling summarization
        $memory->summarizeIfNeeded($conversation, $ai);
    }

    private function resolveCustomer(): Customer
    {
        // Instagram customers are identified by "ig_{senderId}" to avoid
        // collision with WhatsApp phone numbers in the shared customers table.
        return Customer::firstOrCreate(
            [
                'phone'     => "ig_{$this->senderId}",
                'client_id' => $this->client->id,
            ],
            [
                'phone_lid'  => $this->senderId,
                'wa_session' => 'instagram',
                'client_id'  => $this->client->id,
            ]
        );
    }
}
