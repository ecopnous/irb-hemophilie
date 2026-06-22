<?php

namespace App\Notifications;

use App\Models\ClinicalMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ClinicalMessagePatientMail extends Notification
{
    use Queueable;

    public function __construct(
        public readonly ClinicalMessage $message,
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
        $patient = $this->message->dossierPatient;
        $patientName = trim(ucfirst((string) $patient?->prenom) . ' ' . ucfirst((string) $patient?->nom));
        $senderName = $this->message->senderDisplayName();
        $viewUrl = URL::temporarySignedRoute(
            'messaging.patient.show',
            now()->addDays(30),
            ['message' => $this->message->id],
        );

        $mail = (new MailMessage)
            ->subject('Message clinique : ' . $this->message->subject)
            ->greeting($patientName !== '' ? "Bonjour {$patientName}," : 'Bonjour,')
            ->line('Vous avez recu un message de votre equipe de soins.')
            ->line('Expediteur : ' . $senderName)
            ->line('Objet : ' . $this->message->subject)
            ->line('---')
            ->line(str($this->message->body)->limit(500)->value())
            ->action('Lire le message complet', $viewUrl)
            ->line('Ce lien est valide 30 jours. Ne partagez pas cette URL.');

        if ($this->message->attachments->isNotEmpty()) {
            $mail->line('Pieces jointes : ' . $this->message->attachments->pluck('original_name')->implode(', '));
        }

        return $mail->salutation('Cordialement, L equipe medicale IRB');
    }
}
