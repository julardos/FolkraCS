<?php

namespace Modules\AI\Services;

use App\Models\Client;
use App\Models\KnowledgeBase;
use App\Models\PromptBlock;
use App\Models\Setting;
use Carbon\Carbon;

class PromptBuilder
{
    public function build(): string
    {
        $blocks = [];

        // Assemble enabled prompt blocks in order
        $promptBlocks = PromptBlock::enabled()->get();
        foreach ($promptBlocks as $block) {
            $blocks[] = $this->interpolate($block->content);
        }

        // Fallback system prompt if no prompt blocks found
        if (empty($blocks)) {
            $fallbackInstruction = Client::first()?->ai_instruction
                ?? Setting::get('ai.system_prompt')
                ?? 'You are a helpful AI customer service assistant.';
            $blocks[] = $this->interpolate($fallbackInstruction);
        }

        // Append active knowledge base entries
        $kbEntries = KnowledgeBase::active()->get();
        foreach ($kbEntries as $kb) {
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
