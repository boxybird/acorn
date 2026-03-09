<?php

namespace App\Notifications;

use App\Models\Intake;
use App\Models\IntakeFlag;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IntakeFlaggedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Intake $intake,
        private readonly IntakeFlag $intakeFlag,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $childName = $this->intake->child_name ?? 'your child';
        /** @var string $formTitle */
        $formTitle = $this->intakeFlag->formResponse?->schema_key;

        return (new MailMessage)
            ->subject(sprintf("Action Needed: %s's Intake", $childName))
            ->greeting('Hello!')
            ->line(sprintf("A form in %s's intake requires your attention.", $childName))
            ->line('**Form:** '.$formTitle)
            ->line('**Reason:** '.$this->intakeFlag->reason)
            ->action('Review & Update', url('/intake'))
            ->line('Please log in to review and correct the flagged form.');
    }
}
