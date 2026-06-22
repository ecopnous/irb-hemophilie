<?php

namespace App\Notifications;

use App\Models\ClinicalMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InternalClinicalMessageNotification extends Notification
{
    use Queueable;

    public function __construct(public ClinicalMessage $message) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ((bool) data_get($notifiable, 'metadata.clinical_message_email_enabled', false)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->message->subject)
            ->line($this->message->excerpt(180))
            ->action('Ouvrir la conversation', route('messaging.inbox', ['message' => $this->message->id]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'thread_id' => $this->message->thread_id,
            'subject' => $this->message->subject,
            'excerpt' => $this->message->excerpt(180),
            'priority' => $this->message->priority->value,
            'category' => $this->message->category->value,
            'sender' => $this->message->senderDisplayName(),
            'url' => route('messaging.inbox', ['message' => $this->message->id]),
        ];
    }
}
