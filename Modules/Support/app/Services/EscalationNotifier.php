<?php

namespace Modules\Support\Services;

use App\Models\NotificationSetting;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\WhatsApp\Services\WahaClient;

class EscalationNotifier
{
    public function __construct(private WahaClient $waha) {}

    public function notify(SupportTicket $ticket): void
    {
        $settings = NotificationSetting::current();

        if (! $settings->shouldNotifyFor($ticket->kendala_type)) {
            return;
        }

        $message = $this->formatMessage($ticket);

        if ($settings->channel_wa && $settings->wa_number) {
            $this->notifyViaWa($settings->wa_number, $message, $ticket->conversation->wa_session);
        }

        if ($settings->channel_email && $settings->email) {
            $this->notifyViaEmail($settings->email, $ticket, $message);
        }
    }

    private function formatMessage(SupportTicket $ticket): string
    {
        $typeLabel = match ($ticket->kendala_type) {
            'complaint'       => 'Komplain Pelanggan',
            'question'        => 'Pertanyaan (tidak bisa dijawab AI)',
            'escalation'      => 'Minta Bicara Admin',
            'schedule_change' => 'Ubah Jadwal Booking',
            default           => 'Kendala',
        };

        $name  = $ticket->customer_name ?? 'Tidak diketahui';
        $phone = $ticket->customer_phone ?? '-';

        return implode("\n", [
            '🚨 *Eskalasi CS - FolkraCS*',
            "Pelanggan: {$name} ({$phone})",
            "Tipe: {$typeLabel}",
            "Masalah: {$ticket->ac_problem}",
            '',
            'Buka dashboard untuk ambil alih percakapan.',
        ]);
    }

    private function notifyViaWa(string $number, string $message, string $session): void
    {
        $chatId = "{$number}@s.whatsapp.net";

        // Guard: don't send to the bot's own session number
        $botNumber = preg_replace('/\D/', '', \App\Models\Setting::get('wa.base_url', ''));
        if ($number === $botNumber) {
            Log::warning('EscalationNotifier: escalation WA number matches bot — skipped to prevent loop.');
            return;
        }

        $this->waha->sendText($chatId, $message, $session);
    }

    private function notifyViaEmail(string $email, SupportTicket $ticket, string $message): void
    {
        try {
            Mail::raw($message, fn($m) => $m->to($email)->subject('Eskalasi CS: ' . ($ticket->customer_name ?? $ticket->customer_phone)));
        } catch (\Throwable $e) {
            Log::error('EscalationNotifier: email failed', ['error' => $e->getMessage()]);
        }
    }
}
