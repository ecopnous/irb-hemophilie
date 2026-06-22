<?php

namespace App\Console\Commands;

use App\Models\RendezVous;
use App\Services\ClinicalMessagingService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifierRendezVousPatient48h extends Command
{
    protected $signature = 'app:notifier-rendezvous-patient-48h';

    protected $description = 'Cree un message clinique de rappel pour les patients 48 heures avant leur rendez-vous';

    public function handle(ClinicalMessagingService $messagingService): int
    {
        $targetDate = Carbon::now()->addDays(2)->toDateString();

        $rendezVous = RendezVous::query()
            ->with(['doctor', 'dossierPatient'])
            ->whereDate('date_rendez_vous', $targetDate)
            ->where('rappel_patient_48h_envoye', false)
            ->whereNotNull('dossier_patient_id')
            ->whereHas('dossierPatient', function ($query): void {
                $query->whereNotNull('email')->where('email', '!=', '');
            })
            ->get();

        $sentCount = 0;

        foreach ($rendezVous as $rdv) {
            $patient = $rdv->dossierPatient;

            if ($patient === null || blank($patient->email)) {
                continue;
            }

            $messagingService->sendAppointmentReminder(
                patient: $patient,
                appointmentAt: $rdv->date_rendez_vous,
                doctor: $rdv->doctor,
            );

            $rdv->update(['rappel_patient_48h_envoye' => true]);

            $sentCount++;
        }

        $this->info("{$sentCount} rappel(s) patient envoyé(s) pour les rendez-vous du {$targetDate}.");

        return self::SUCCESS;
    }
}
