<?php

namespace Database\Seeders;

use App\Models\DossierPatient;
use App\Models\User;
use Database\Factories\DossierPatientFactory;
use Illuminate\Database\Seeder;

class DossierPatientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $creatorId = User::query()->value('id');

        if (!$creatorId) {
            $creatorId = User::factory()->create([
                'hopital_id' => 1,
                'email' => 'seed-patient@example.test',
            ])->id;
        }

        DossierPatient::withoutEvents(function () use ($creatorId): void {
            DossierPatientFactory::new()
                ->count(6000)
                ->create([
                    'user_id' => $creatorId,
                    'hopital_id' => 1,
                ]);
        });
    }
}
