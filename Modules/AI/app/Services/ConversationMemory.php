<?php

namespace Modules\AI\Services;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class ConversationMemory
{
    /**
     * Load recent messages and inject previous summary if available.
     */
    public function load(Conversation $conversation, int $limit = 6): array
    {
        $messages = [];

        // Prepend rolling summary as context if present
        if (! empty($conversation->summary)) {
            $messages[] = [
                'role'    => 'system',
                'content' => "Ringkasan percakapan sebelumnya:\n" . $conversation->summary,
            ];
        }

        $recentMessages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn(Message $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray();

        return array_merge($messages, $recentMessages);
    }

    /**
     * Summarize older messages if total conversation length exceeds the threshold.
     */
    public function summarizeIfNeeded(
        Conversation $conversation,
        OpenRouterClient $ai,
        int $threshold = 8,
        int $keepRecent = 6
    ): void {
        $allMessages = $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'asc')
            ->get();

        if ($allMessages->count() <= $threshold) {
            return;
        }

        // Older messages to compress into summary
        $messagesToSummarize = $allMessages->slice(0, $allMessages->count() - $keepRecent);
        if ($messagesToSummarize->isEmpty()) {
            return;
        }

        $transcript = $messagesToSummarize->map(fn($m) => ucfirst($m->role) . ": " . $m->content)->implode("\n");

        $systemPrompt = "You are a concise conversation summarizer. Summarize the following conversation in 1-2 clear, informative sentences in Indonesian. Retain key facts, customer inquiries, and critical case details.";
        
        $userPrompt = (! empty($conversation->summary) ? "Ringkasan sebelumnya:\n" . $conversation->summary . "\n\nPesan tambahan:\n" : "Percakapan:\n") . $transcript;

        try {
            $summary = $ai->chat($systemPrompt, [
                ['role' => 'user', 'content' => $userPrompt],
            ]);

            if (! empty($summary)) {
                $conversation->update(['summary' => trim($summary)]);
            }
        } catch (\Throwable $e) {
            Log::warning('ConversationMemory: Summarization failed', ['error' => $e->getMessage()]);
        }
    }

    public function append(Conversation $conversation, string $role, string $content, ?string $rawOutput = null): Message
    {
        return $conversation->messages()->create([
            'role'       => $role,
            'content'    => $content,
            'raw_output' => $rawOutput,
        ]);
    }
}
