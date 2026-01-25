<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nin' => $this->faker->unique()->regexify('10V-F003-\d{5}'),
            'photo' => $this->faker->imageUrl(200, 200, 'people'),
            'nom' => strtoupper($this->faker->lastName()),
            'postnom' => strtoupper($this->faker->lastName()),
            'prenom' => $this->faker->firstName(),
            'genre' => $this->faker->randomElement(['M', 'F']),
            'etat_civil' => $this->faker->randomElement(['Célibataire', 'Marié', 'Divorcé']),
            'telephone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
            'date_naissance' => $this->faker->date('Y-m-d', '-18 years'),

            // Informations régionales
            'nationalite' => 'Congolaise (RDC)',
            'province' => $this->faker->state(),
            'territoire' => $this->faker->city(),
            'commune' => $this->faker->streetName(),
            'quartier' => $this->faker->word(),
            'avenue' => $this->faker->streetName(),
            'numero_habitation' => $this->faker->buildingNumber(),

            // Autres informations
            'langues' => json_encode($this->faker->randomElements(['Français', 'Lingala', 'Swahili', 'Tshiluba', 'Kikongo'], 2)),
            'type_dossier' => $this->faker->randomElement(['Normal', 'Urgent', 'VIP']),
            'categorisation' => $this->faker->randomElement(['Civil', 'Militaire', 'Dépendant']),
            'prise_en_charge' => $this->faker->company(),
            'ins' => $this->faker->numerify('INS-######'),
            'note' => $this->faker->sentence(),

            // Informations complémentaires
            'grade' => $this->faker->word(),
            'unite' => $this->faker->word(),
            'matricule' => strtoupper($this->faker->bothify('??-####')),
            'groupe_sang' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'electrophorese' => $this->faker->randomElement(['AA', 'AS', 'SS']),

            'pere' => $this->faker->name('male'),
            'mere' => $this->faker->name('female'),
            'epoux' => $this->faker->name(),
            'parent_tuteur' => null, // Généralement lié à un autre ID patient
            'personne_contacter' => $this->faker->name() . ' (' . $this->faker->phoneNumber() . ')',

            'ethnie' => $this->faker->word(),
            'province_orginine' => $this->faker->state(),
            'Race' => 'Noire',
        ];
    }

    private function generateUniqueNin()
    {
        // 1. Trouver le dernier NIN enregistré en base
        $lastPatient = Patient::where('nin', 'LIKE', '10V-F003-%')
            ->orderBy('nin', 'desc')
            ->first();

        if ($lastPatient) {
            // Extraire le numéro (les 5 derniers chiffres), l'incrémenter
            $lastNumber = (int) substr($lastPatient->nin, -5);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        // 2. Créer le nouveau code
        $newCode = "10V-F003-" . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        // 3. Sécurité supplémentaire : si par hasard le code existe déjà (concurrence)
        // on incrémente jusqu'à trouver un trou libre
        while (Patient::where('nin', $newCode)->exists()) {
            $nextNumber++;
            $newCode = "10V-F003-" . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        return $newCode;
    }
}
