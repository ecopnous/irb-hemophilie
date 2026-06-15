<?php

namespace Database\Factories;

use App\Models\DossierPatient;
use App\Services\Patient\PremierSigneService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DossierPatient>
 */
class DossierPatientFactory extends Factory
{
    protected $model = DossierPatient::class;

    public function configure(): static
    {
        return $this->afterCreating(function (DossierPatient $patient) {
            $service = app(PremierSigneService::class);
            $form = [];

            foreach ($service->definitions() as $definition) {
                $present = fake()->boolean(75);

                $form[$definition->key] = [
                    'present' => $present ? 1 : 0,
                    'value' => $present ? fake()->numberBetween(0, 12) : null,
                    'comment' => fake()->optional(0.35)->sentence(),
                ];
            }

            $service->sync($patient, $form);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $ninSequence = 1;

        $genre = fake()->randomElement(['M', 'F']);

        return [
            'nin' => sprintf('NIN-%s-%06d', now()->format('ymd'), $ninSequence++),
            'nom' => fake()->lastName(),
            'postnom' => fake()->lastName(),
            'prenom' => fake()->firstName($genre === 'M' ? 'male' : 'female'),
            'genre' => $genre,
            'etat_civil' => fake()->randomElement(['Célibataire', 'Marié', 'Divorcé', 'Veu(f)ve']),
            'telephone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'date_naissance' => fake()->dateTimeBetween('-70 years', '-1 years')->format('Y-m-d'),
            'quartier' => fake()->streetSuffix(),
            'avenue' => fake()->streetName(),
            'num_habitation' => (string) fake()->numberBetween(1, 9999),
            'ins' => strtoupper(fake()->bothify('INS-########')),
            'note' => fake()->optional()->sentence(),
            'nom_pere' => fake()->name('male'),
            'province_pere' => 'Kinshasa',
            'tribut_pere' => fake()->optional()->word(),
            'profession_pere' => fake()->jobTitle(),
            'nom_mere' => fake()->name('female'),
            'province_mere' => 'Kinshasa',
            'tribut_mere' => fake()->optional()->word(),
            'profession_mere' => fake()->jobTitle(),
            'poids_naissance' => (string) fake()->numberBetween(1800, 4500),
            'type_famille' => fake()->randomElement(['nucleaire', 'elargie', 'monoparentale']),
            'rang_fratrie' => fake()->numberBetween(1, 8),
            'nb_freres' => fake()->numberBetween(0, 6),
            'nb_soeurs' => fake()->numberBetween(0, 6),
            'deces_freres' => fake()->numberBetween(0, 2),
            'deces_soeurs' => fake()->numberBetween(0, 2),
            'histoire_famille_supplementaire' => fake()->optional()->sentence(),
            'is_hemophile' => fake()->boolean(40),
            'is_anemique' => fake()->boolean(30),
            'is_dead' => false,
            'age_gestationnel' => fake()->numberBetween(28, 42),
            'allaitement_maternel' => fake()->boolean(80),
            'med_traditionnel' => fake()->boolean(25),
            'moringa_oleifera' => fake()->boolean(20),
            'indications' => fake()->optional()->words(3, true),
            'duree_prise' => fake()->optional()->randomElement(['7 jours', '14 jours', '1 mois']),
            'vaccins' => fake()->optional()->sentence(),
            'histoire_perso_supplementaire' => fake()->optional()->paragraph(),
            'premier_signes_supplementaires' => fake()->optional()->sentence(),
            'antecedents_medicales' => fake()->optional()->words(3, true),
            'antecedents_chirurgicaux' => fake()->optional()->words(3, true),
            'antecedents_familiaux' => fake()->optional()->words(3, true),
            'antecedents_obstetricaux' => fake()->optional()->words(3, true),
            'antecedents_gynocola' => fake()->optional()->words(3, true),
            'antecedents_neurologiques' => fake()->optional()->words(3, true),
            'antecedents_cardiovasculaires' => fake()->optional()->words(3, true),
            'antecedents_digestifs' => fake()->optional()->words(3, true),
            'antecedents_endocrinologiques' => fake()->optional()->words(3, true),
            'antecedents_hematologiques' => fake()->optional()->words(3, true),
            'antecedents_supplementaires' => fake()->optional()->sentence(),
            'province_id' => 10,
            'ville_id' => 1,
            'commune_id' => fake()->numberBetween(1, 24),
            'adresses_supplementaires' => fake()->optional()->address(),
            'assurance_id' => null,
            'country_id' => 52,
            'dossier_patient_id' => null,
            'user_id' => 1,
            'categorisation_id' => null,
            'hopital_id' => 1,
        ];
    }
}
