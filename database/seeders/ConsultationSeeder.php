<?php

namespace Database\Seeders;

use App\Models\Configs\Departement;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\User;
use Database\Factories\ConsultationFactory;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class ConsultationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!DossierPatient::query()->exists()) {
            $this->call([
                DossierPatientSeeder::class,
            ]);
        }

        $userId = User::query()->value('id');
        if (!$userId) {
            $userId = User::factory()->create([
                'hopital_id' => 1,
                'email' => 'seed-consultation@example.test',
            ])->id;
        }

        $patientIds = DossierPatient::query()->pluck('id')->all();
        $departementIds = Departement::query()->pluck('id')->all();

        if (empty($patientIds) || empty($departementIds)) {
            return;
        }

        $referenceSequence = 1;
        Consultation::withoutEvents(function () use ($patientIds, $departementIds, $userId, &$referenceSequence): void {
            ConsultationFactory::new()
                ->count(6000)
                ->state(new Sequence(function () use ($patientIds, $departementIds, $userId, &$referenceSequence): array {
                    $type = fake()->randomElement(['consultation', 'depistage']);
                    $prefix = $type === 'consultation' ? 'C' : 'D';
                    $moisPrefix = $type === 'consultation' ? 'C' : 'D';

                    return [
                        'type' => $type,
                        'reference' => sprintf('R-%s%s-%05d', now()->format('y'), $prefix, $referenceSequence++),
                        'mois' => $moisPrefix . fake()->numberBetween(1, 20),
                        'dossier_patient_id' => $patientIds[array_rand($patientIds)],
                        'departement_id' => $departementIds[array_rand($departementIds)],
                        'user_id' => $userId,
                        'hopital_id' => 1,
                    ];
                }))
                ->create();
        });
    }
}
