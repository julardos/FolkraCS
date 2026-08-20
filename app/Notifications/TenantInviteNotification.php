<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class TenantInviteNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $resetUrl = url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()], false));

        return (new MailMessage)
            ->subject('You have been invited — set your password')
            ->greeting('Welcome!')
            ->line('An account has been created for you on ' . config('app.name') . '.')
            ->action('Set your password', $resetUrl)
            ->line('If you did not expect this email, you can ignore it.');
    }
}
