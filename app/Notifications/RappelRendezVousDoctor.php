<?php

namespace App\Notifications;

use App\Models\RendezVous;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RappelRendezVousDoctor extends Notification
{
    use Queueable;

    public function __construct(
        public readonly RendezVous $rendezVous,
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
        $doctorName = trim((string) ($notifiable->name ?? 'Docteur'));
        $patientName = $this->rendezVous->patient_name;
        $dateHeure = $this->rendezVous->date_rendez_vous->translatedFormat('l d F Y \à H:i');

        return (new MailMessage)
            ->subject('Rappel : rendez-vous dans 48 heures')
            ->greeting("Bonjour Dr {$doctorName},")
            ->line('Nous vous rappelons que vous avez un rendez-vous prévu dans 48 heures.')
            ->line("Patient : {$patientName}")
            ->line("Date et heure : {$dateHeure}")
            ->salutation('Cordialement,');
    }
}
