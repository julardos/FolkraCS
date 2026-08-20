<?php

namespace Modules\AI\DTOs;

class ParsedOutput
{
    public function __construct(
        public readonly string $humanMessage,
        public readonly bool $hasBooking = false,
        public readonly bool $hasKendala = false,
        public readonly ?array $booking = null,
        public readonly ?array $kendala = null,
    ) {}
}
