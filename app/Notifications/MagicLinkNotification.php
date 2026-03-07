<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MagicLinkNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $token) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/intake/verify/'.$this->token);

        return (new MailMessage)
            ->subject('Continue Your JumpStart Intake')
            ->greeting('Hello!')
            ->line('Click the button below to continue your intake forms.')
            ->action('Continue Your Intake', $url)
            ->line('This link will expire in 30 minutes.')
            ->line('If you did not request this, no action is needed.');
    }
}
