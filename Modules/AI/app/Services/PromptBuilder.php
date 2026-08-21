<?php

namespace Modules\AI\Services;

use App\Models\Client;
use App\Models\KnowledgeBase;
use App\Models\PromptBlock;
use App\Models\Setting;
use Carbon\Carbon;

class PromptBuilder
{
    public function build(?Client $client = null): string
    {
        $client ??= Client::first();
        $blocks   = [];

        // Assemble enabled prompt blocks in order (scoped to client)
        $promptBlocks = PromptBlock::enabled()
            ->when($client, fn($q) => $q->where('client_id', $client->id))
            ->get();

        foreach ($promptBlocks as $block) {
            $blocks[] = $this->interpolate($block->content);
        }

        // Fallback to client AI instruction or global setting
        if (empty($blocks)) {
            $fallbackInstruction = $client?->ai_instruction
                ?? Setting::get('ai.system_prompt')
                ?? 'You are a helpful AI customer service assistant.';
            $blocks[] = $this->interpolate($fallbackInstruction);
        }

        // Append active knowledge base entries scoped to this client
        $kbQuery = KnowledgeBase::active()
            ->when($client, fn($q) => $q->where('client_id', $client->id));

        foreach ($kbQuery->get() as $kb) {
            $blocks[] = "### {$kb->title}\n{$kb->content}";
        }

        return implode("\n\n---\n\n", $blocks);
    }

    private function interpolate(string $content): string
    {
        $tz   = Setting::get('timezone', 'Asia/Makassar');
        $now  = Carbon::now($tz);

        return str_replace(
            ['{{date}}', '{{day}}', '{{time}}', '{{timezone}}'],
            [$now->format('Y-m-d'), $now->isoFormat('dddd'), $now->format('HH:mm'), $tz],
            $content
        );
    }
}
