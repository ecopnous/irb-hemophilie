<?php

namespace Database\Factories;

use App\Models\Consultation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Consultation>
 */
class ConsultationFactory extends Factory
{
    protected $model = Consultation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isConsultation = fake()->boolean(80);
        $type = $isConsultation ? 'consultation' : 'depistage';
        $prefix = $isConsultation ? 'C' : 'D';
        $seq = fake()->numberBetween(1, 999999);

        return [
            'reference' => sprintf('R-%s%s-%05d', now()->format('y'), $prefix, $seq),
            'type' => $type,
            'type_fichier' => fake()->randomElement(['hemophile', 'redac', 'standard']),
            'is_project_period' => false,
            'symptomes' => fake()->optional()->sentence(),
            'examen_clinique' => fake()->optional()->sentence(),
            'diagnostic_presomption' => fake()->optional()->sentence(),
            'complement_anamnese' => fake()->optional()->paragraph(),
            'plan_traitement_conduite' => fake()->optional()->paragraph(),
            'prescription_medicale' => fake()->optional()->sentence(),
            'rendez_vous_medical' => fake()->optional()->sentence(),
            'poids' => fake()->numberBetween(8, 130),
            'temperature' => fake()->numberBetween(35, 41),
            'systolite' => fake()->numberBetween(9, 18),
            'taille' => fake()->numberBetween(70, 200),
            'perimetre_cranien' => fake()->optional()->numberBetween(30, 60),
            'perimetre_brachial' => fake()->optional()->numberBetween(10, 40),
            'frequence_cardiaque' => fake()->optional()->numberBetween(50, 150),
            'frequence_respiratoire' => fake()->optional()->numberBetween(10, 40),
            'diastolique' => fake()->numberBetween(5, 11),
            'saturation_oxygene' => fake()->optional()->numberBetween(85, 100),
            'glycemie' => fake()->optional()->numberBetween(60, 180),
            'mois' => $prefix . fake()->numberBetween(1, 20),
            'issue' => fake()->optional()->randomElement(['ambulatoire', 'hospitalisation', 'suivi_medical', 'transfert', 'deces', 'autres']),
            'autre_issue' => fake()->optional()->sentence(),
            'cause_issue' => fake()->optional()->sentence(),
            'dossier_patient_id' => 1,
            'departement_id' => 5,
            'service_id' => null,
            'user_id' => 1,
            'projet_id' => null,
            'assurance_id' => null,
            'prelevement_effectue' => fake()->boolean(70),
            'laboratoire_id' => null,
            'imagerie_id' => null,
            'prescription_id' => null,
            'hospitalisation_id' => null,
            'facturation_id' => null,
            'is_clore' => fake()->boolean(20),
            'is_visite_program' => false,
            'consultation_source_id' => null,
            'hopital_id' => 1,
        ];
    }
}
