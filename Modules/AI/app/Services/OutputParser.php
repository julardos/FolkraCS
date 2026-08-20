<?php

namespace Modules\AI\Services;

use Modules\AI\DTOs\ParsedOutput;

class OutputParser
{
    public function parse(string $output, string $phone, string $phoneLid, ?string $pushName): ParsedOutput
    {
        $hasBooking = str_contains($output, '%%BOOKING_CONFIRMED%%') && str_contains($output, '%%END_BOOKING%%');
        $hasKendala = str_contains($output, '%%KENDALA_DETECTED%%') && str_contains($output, '%%END_KENDALA%%');

        if ($hasBooking) {
            $json       = $this->extractBlock($output, '%%BOOKING_CONFIRMED%%', '%%END_BOOKING%%');
            $humanMsg   = $this->beforeMarker($output, '%%BOOKING_CONFIRMED%%');
            $booking    = $this->sanitize(json_decode($json, true) ?? [], $phone, $phoneLid, $pushName);
            $booking    = $this->sanitizeBookingDates($booking);

            return new ParsedOutput(humanMessage: $humanMsg, hasBooking: true, booking: $booking);
        }

        if ($hasKendala) {
            $json     = $this->extractBlock($output, '%%KENDALA_DETECTED%%', '%%END_KENDALA%%');
            $humanMsg = $this->beforeMarker($output, '%%KENDALA_DETECTED%%');
            $kendala  = $this->sanitize(json_decode($json, true) ?? [], $phone, $phoneLid, $pushName);

            return new ParsedOutput(humanMessage: $humanMsg, hasKendala: true, kendala: $kendala);
        }

        return new ParsedOutput(humanMessage: $output);
    }

    private function extractBlock(string $text, string $open, string $close): string
    {
        $start = strpos($text, $open) + strlen($open);
        $end   = strpos($text, $close);
        return trim(substr($text, $start, $end - $start));
    }

    private function beforeMarker(string $text, string $marker): string
    {
        return trim(substr($text, 0, strpos($text, $marker)));
    }

    private function sanitize(array $data, string $phone, string $phoneLid, ?string $pushName): array
    {
        $data['customer_phone']     = $phone;
        $data['customer_phone_lid'] = $phoneLid;
        $data['customer_name']      = $data['customer_name'] ?? $pushName;

        // Strip unfilled placeholders like [nama] or [alamat]
        foreach ($data as $key => $value) {
            if (is_string($value) && preg_match('/^\[.*\]$/', trim($value))) {
                $data[$key] = null;
            }
        }

        return $data;
    }

    private function sanitizeBookingDates(array $data): array
    {
        $timedate = $data['preferred_timedate'] ?? '';

        if (empty($timedate) || str_contains($timedate, '[') || str_contains($timedate, 'contoh')) {
            $data['preferred_timedate'] = null;
            return $data;
        }

        $parsed = \Carbon\Carbon::parse($timedate);
        $data['preferred_timedate'] = $parsed->toIso8601String();

        if (isset($data['is_inverter']) && is_string($data['is_inverter'])) {
            $data['is_inverter'] = strtolower($data['is_inverter']) === 'true';
        }

        return $data;
    }
}
