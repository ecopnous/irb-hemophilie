<?php

namespace App\Console\Commands;

use App\Models\RendezVous;
use App\Notifications\RappelRendezVousDoctor;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifierRendezVous48h extends Command
{
    protected $signature = 'app:notifier-rendezvous-48h';

    protected $description = 'Envoie un rappel par email aux médecins 48 heures avant leurs rendez-vous';

    public function handle(): int
    {
        $targetDate = Carbon::now()->addDays(2)->toDateString();

        $rendezVous = RendezVous::query()
            ->with('doctor')
            ->whereDate('date_rendez_vous', $targetDate)
            ->where('rappel_48h_envoye', false)
            ->whereHas('doctor', function ($query): void {
                $query->whereNotNull('email')
                    ->where('email', '!=', '');
            })
            ->get();

        $sentCount = 0;

        foreach ($rendezVous as $rdv) {
            $doctor = $rdv->doctor;

            if ($doctor === null || blank($doctor->email)) {
                continue;
            }

            $doctor->notify(new RappelRendezVousDoctor($rdv));

            $rdv->update(['rappel_48h_envoye' => true]);

            $sentCount++;
        }

        $this->info("{$sentCount} rappel(s) envoyé(s) pour les rendez-vous du {$targetDate}.");

        return self::SUCCESS;
    }
}
