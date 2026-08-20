<?php

namespace Modules\AI\Jobs;

use App\Models\Conversation;
use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Modules\AI\Services\ConversationMemory;
use Modules\AI\Services\OpenRouterClient;
use Modules\AI\Services\OutputParser;
use Modules\AI\Services\PromptBuilder;
use Modules\Support\Services\EscalationNotifier;
use Modules\Support\Services\TicketService;
use Modules\WhatsApp\Services\WahaClient;

class ProcessMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 90;

    public function __construct(
        public readonly Customer $customer,
        public readonly string $chatId,
        public readonly string $userMessage,
        public readonly string $session,
    ) {}

    public function handle(
        PromptBuilder $promptBuilder,
        ConversationMemory $memory,
        OpenRouterClient $ai,
        OutputParser $parser,
        WahaClient $waha,
        TicketService $tickets,
        EscalationNotifier $notifier,
    ): void {
        // Get or create active conversation
        $conversation = $this->customer->activeConversation ?? Conversation::create([
            'customer_id' => $this->customer->id,
            'wa_session'  => $this->session,
            'status'      => 'active',
        ]);

        // Save user message
        $memory->append($conversation, 'user', $this->userMessage);

        // Build prompt and history
        $systemPrompt = $promptBuilder->build();
        $history      = $memory->load($conversation);

        // Call AI
        try {
            $rawOutput = $ai->chat($systemPrompt, $history);
        } catch (\Throwable $e) {
            Log::error('ProcessMessageJob: AI call failed', ['error' => $e->getMessage(), 'customer' => $this->customer->id]);
            return;
        }

        // Parse AI output
        $parsed = $parser->parse(
            $rawOutput,
            $this->customer->phone,
            $this->customer->phone_lid ?? $this->chatId,
            $this->customer->push_name
        );

        // Save assistant message
        $memory->append($conversation, 'assistant', $parsed->humanMessage, $rawOutput);

        // Send reply to customer
        if (! empty($parsed->humanMessage)) {
            $waha->sendText($this->chatId, $parsed->humanMessage, $this->session);
        }

        // Handle kendala
        if ($parsed->hasKendala && $parsed->kendala) {
            $ticket = $tickets->create($this->customer, $conversation, $parsed->kendala);
            $notifier->notify($ticket);
            $conversation->update(['status' => 'escalated']);
        }
    }
}
