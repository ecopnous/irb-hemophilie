<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DepartementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        
        $departements = [
            ['name' => 'Urgences', 'description' => 'Service des urgences médicales', 'color' => 'bg-danger', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Médecine générale', 'description' => 'Service de médecine générale', 'color' => 'bg-primary', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Chirurgie', 'description' => 'Service de chirurgie générale', 'color' => 'bg-warning', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pédiatrie', 'description' => 'Service de pédiatrie', 'color' => 'bg-info', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gynécologie', 'description' => 'Service de gynécologie', 'color' => 'bg-success', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Obstétrique', 'description' => 'Service d\'obstétrique', 'color' => 'bg-teal', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Cardiologie', 'description' => 'Service de cardiologie', 'color' => 'bg-danger', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Neurologie', 'description' => 'Service de neurologie', 'color' => 'bg-indigo', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Orthopédie', 'description' => 'Service d\'orthopédie', 'color' => 'bg-secondary', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Ophtalmologie', 'description' => 'Service d\'ophtalmologie', 'color' => 'bg-cyan', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oto-rhino-laryngologie', 'description' => 'Service ORL', 'color' => 'bg-orange', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Dermatologie', 'description' => 'Service de dermatologie', 'color' => 'bg-warning', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pneumologie', 'description' => 'Service de pneumologie', 'color' => 'bg-info', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Gastro-entérologie', 'description' => 'Service de gastro-entérologie', 'color' => 'bg-success', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Urologie', 'description' => 'Service d\'urologie', 'color' => 'bg-primary', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Oncologie', 'description' => 'Service d\'oncologie', 'color' => 'bg-danger', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Radiologie', 'description' => 'Service de radiologie', 'color' => 'bg-dark', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Laboratoire', 'description' => 'Service de laboratoire', 'color' => 'bg-cyan', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Pharmacie', 'description' => 'Service de pharmacie', 'color' => 'bg-success', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Réanimation', 'description' => 'Service de réanimation', 'color' => 'bg-danger', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Bloc opératoire', 'description' => 'Bloc opératoire', 'color' => 'bg-warning', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Anesthésie', 'description' => 'Service d\'anesthésie', 'color' => 'bg-indigo', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Psychiatrie', 'description' => 'Service de psychiatrie', 'color' => 'bg-teal', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Médecine interne', 'description' => 'Service de médecine interne', 'color' => 'bg-primary', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Rhumatologie', 'description' => 'Service de rhumatologie', 'color' => 'bg-orange', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Endocrinologie', 'description' => 'Service d\'endocrinologie', 'color' => 'bg-info', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Néphrologie', 'description' => 'Service de néphrologie', 'color' => 'bg-cyan', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Hématologie', 'description' => 'Service d\'hématologie', 'color' => 'bg-danger', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Physiothérapie', 'description' => 'Service de physiothérapie', 'color' => 'bg-success', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Nutrition', 'description' => 'Service de nutrition', 'color' => 'bg-warning', 'created_at' => $now, 'updated_at' => $now],
        ];

        // 2. On insère sans passer par un Modèle ou une Factory
        DB::table('departements')->insert($departements);
    }
}
