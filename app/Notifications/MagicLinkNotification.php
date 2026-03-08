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
            ->subject('Your JumpStart Intake Link')
            ->greeting('Welcome!')
            ->line("You're one step away from starting your intake paperwork with JumpStart Autism Collective.")
            ->line('Click the button below to securely access your forms. You can complete them at your own pace and pick up where you left off anytime.')
            ->action('Continue Your Intake', $url)
            ->line('This link will expire in 30 minutes. If it expires, simply request a new one from the intake page.')
            ->line("If you didn't request this link, you can safely ignore this email.");
    }
}
