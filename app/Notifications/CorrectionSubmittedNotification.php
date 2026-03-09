<?php

namespace App\Notifications;

use App\Models\Intake;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrectionSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Intake $intake) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $childName = $this->intake->child_name ?? 'Unknown';

        return (new MailMessage)
            ->subject(sprintf('Intake Updated: %s', $childName))
            ->greeting('Hello!')
            ->line(sprintf("%s's intake has been updated with corrections.", $childName))
            ->line('Please review the updated submission.')
            ->action('Review Intake', url(sprintf('/staff/intakes/%s', $this->intake->id)));
    }
}
