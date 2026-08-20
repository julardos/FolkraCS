<?php

namespace Modules\AI\Services;

use App\Models\Conversation;
use App\Models\Message;

class ConversationMemory
{
    public function load(Conversation $conversation, int $limit = 20): array
    {
        return $conversation->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->map(fn(Message $m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray();
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
