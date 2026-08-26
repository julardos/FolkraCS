<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TenantInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $token,
        protected string $tenantDomain,
        protected string $clientName,
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $resetUrl = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        $dashboardUrl = 'https://' . $this->tenantDomain;

        return (new MailMessage)
            ->subject("Selamat datang di FolkraCS — Yuk aktifkan {$this->clientName}!")
            ->greeting("Halo, {$notifiable->name}!")
            ->line("Dashboard FolkraCS untuk **{$this->clientName}** sudah siap digunakan.")
            ->line("Alamat dashboard Anda: **{$dashboardUrl}**")
            ->action('Atur Password & Mulai Sekarang', $resetUrl)
            ->line('Setelah masuk, ikuti 4 langkah ini untuk mengaktifkan asisten AI Anda:')
            ->line('**1. Hubungkan WhatsApp** → Buka menu Koneksi → scan QR code dengan ponsel Anda')
            ->line('**2. Isi Knowledge Base** → Masukkan info produk, layanan, harga, dan FAQ bisnis Anda')
            ->line('**3. Konfigurasi AI** → Atur nama, gaya bahasa, dan instruksi asisten Anda')
            ->line('**4. Uji coba** → Kirim pesan WhatsApp ke nomor Anda dan lihat AI menjawab otomatis')
            ->line('Halaman **Mulai** di dalam dashboard akan memandu Anda melalui setiap langkah.')
            ->line('Butuh bantuan? Balas email ini — kami siap membantu.');
    }
}
