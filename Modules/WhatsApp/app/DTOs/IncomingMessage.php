<?php

namespace Modules\WhatsApp\DTOs;

class IncomingMessage
{
    public function __construct(
        public readonly string  $event,
        public readonly string  $session,
        public readonly string  $chatId,        // from — e.g. 6281234@s.whatsapp.net
        public readonly string  $body,          // message text
        public readonly bool    $fromMe,
        public readonly ?string $senderPhone,   // clean phone e.g. 6281234567890
        public readonly ?string $pushName,
    ) {}

    public static function fromWebhook(array $payload): self
    {
        $p          = $payload['payload'] ?? [];
        $senderAlt  = $p['_data']['Info']['SenderAlt'] ?? null;
        $phone      = $senderAlt
            ? preg_replace('/[:@].*/', '', $senderAlt)
            : preg_replace('/@.*/', '', $p['from'] ?? '');

        return new self(
            event:       $payload['event'] ?? '',
            session:     $payload['session'] ?? '',
            chatId:      $p['from'] ?? '',
            body:        $p['body'] ?? '',
            fromMe:      (bool) ($p['fromMe'] ?? false),
            senderPhone: $phone ?: null,
            pushName:    $p['pushName'] ?? null,
        );
    }

    public function isProcessable(): bool
    {
        return $this->event === 'message'
            && ! $this->fromMe
            && ! str_ends_with($this->chatId, '@g.us') // exclude groups
            && ! empty($this->body);
    }
}
