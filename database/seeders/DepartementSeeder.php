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
            ['id' => 1, 'ref' => 'img', 'name' => 'imagerie', 'description' => 'Service d\'imagerie médicale'],
            ['id' => 2, 'ref' => 'labo', 'name' => 'laboratoire', 'description' => 'Service de laboratoire'],
            ['id' => 3, 'ref' => 'mdi', 'name' => 'médecine interne', 'description' => 'Service de médecine interne'],
            ['id' => 4, 'ref' => 'onc', 'name' => "oncologie", "description" => 'Service d\'oncologie'],
            ['id' => 5, 'ref' => 'pdt', 'name' => 'pédiatrie', 'description' => 'Service de pédiatrie'],
            ['id' => 6, 'ref' => 'phm', 'name' => 'pharmacie', 'description' => 'Service de pharmacie'],
        ];

        $services = [
            ['id' => 1, 'name' => 'Biochimi sanguine', 'departement_id' => 2],
            ['id' => 2, 'name' => 'Serologie', 'departement_id' => 2],
            ['id' => 3, 'name' => 'Parasitologie', 'departement_id' => 2],
            ['id' => 4, 'name' => 'Parasitologie', 'departement_id' => 2],
            ['id' => 5, 'name' => 'Hématologie', 'departement_id' => 2],
            ['id' => 6, 'name' => 'Microbiologie', 'departement_id' => 2],
        ];

        $actes = [
            ['id' => 5, 'name' => 'Glycose', 'departement_id' => 2, 'montant' => 18, 'service_id' => 1],
            ['id' => 6, 'name' => 'Amylase', 'departement_id' => 2, 'montant' => 22, 'service_id' => 2],
            ['id' => 7, 'name' => 'FERRITINE', 'departement_id' => 2, 'montant' => 22, 'service_id' => 1],

            ['id' => 1, 'name' => 'Visite medicale', 'departement_id' => 5, 'montant' => 35, 'service_id' => null],
            ['id' => 2, 'name' => 'Visite rendez-vous', 'departement_id' => 5, 'montant' => 35, 'service_id' => null],
            ['id' => 33, 'name' => 'Visite dimanche', 'departement_id' => 5, 'montant' => 40, 'service_id' => null],
            ['id' => 8, 'name' => 'Injection IM/SC', 'departement_id' => 1, 'montant' => 5, 'service_id' => null],
            ['id' => 9, 'name' => 'Injection IV', 'departement_id' => 1, 'montant' => 7, 'service_id' => null],
            ['id' => 10, 'name' => 'Lavement oreille', 'departement_id' => 1, 'montant' => 12, 'service_id' => null],
            ['id' => 11, 'name' => 'Perfusion', 'departement_id' => 5, 'montant' => 25, 'service_id' => null],
            ['id' => 12, 'name' => 'Pansement simple', 'departement_id' => 1, 'montant' => 7, 'service_id' => null],
            ['id' => 13, 'name' => 'Pasement(Gros)', 'departement_id' => 1, 'montant' => 12, 'service_id' => null],
            ['id' => 14, 'name' => 'Circoncision', 'departement_id' => 1, 'montant' => 80, 'service_id' => null],
            ['id' => 15, 'name' => 'Icision abces', 'departement_id' => 5, 'montant' => 40, 'service_id' => null],
            ['id' => 16, 'name' => 'Ablation de l\'ongle', 'departement_id' => 5, 'montant' => 70, 'service_id' => null],
            ['id' => 17, 'name' => 'Aspiration', 'departement_id' => 5, 'montant' => 30, 'service_id' => null],
            ['id' => 18, 'name' => 'Infiltration', 'departement_id' => 5, 'montant' => 30, 'service_id' => null],

            ['id' => 19, 'name' => 'Visite medicale', 'departement_id' => 3, 'montant' => 35, 'service_id' => null],
            ['id' => 20, 'name' => 'Visite rendez-vous', 'departement_id' => 3, 'montant' => 35, 'service_id' => null],
            ['id' => 21, 'name' => 'Visite dimanche', 'departement_id' => 3, 'montant' => 40, 'service_id' => null],
            ['id' => 22, 'name' => 'Injection IM/SC', 'departement_id' => 3, 'montant' => 5, 'service_id' => null],
            ['id' => 23, 'name' => 'Injection IV', 'departement_id' => 3, 'montant' => 5, 'service_id' => null],
            ['id' => 24, 'name' => 'Lavement oreille', 'departement_id' => 3, 'montant' => 12, 'service_id' => null],
            ['id' => 25, 'name' => 'Perfusion', 'departement_id' => 3, 'montant' => 25, 'service_id' => null],
            ['id' => 26, 'name' => 'Pansement simple', 'departement_id' => 3, 'montant' => 7, 'service_id' => null],
            ['id' => 27, 'name' => 'Pasement(Gros)', 'departement_id' => 3, 'montant' => 12, 'service_id' => null],
            ['id' => 28, 'name' => 'Circoncision', 'departement_id' => 3, 'montant' => 80, 'service_id' => null],
            ['id' => 29, 'name' => 'Icision abces', 'departement_id' => 3, 'montant' => 40, 'service_id' => null],
            ['id' => 30, 'name' => 'Ablation de l\'ongle', 'departement_id' => 3, 'montant' => 70, 'service_id' => null],
            ['id' => 31, 'name' => 'Aspiration', 'departement_id' => 3, 'montant' => 30, 'service_id' => null],
            ['id' => 32, 'name' => 'Infiltration', 'departement_id' => 3, 'montant' => 30, 'service_id' => null],
        ];



        // 2. On insère sans passer par un Modèle ou une Factory
        DB::table('departements')->insert($departements);
        DB::table('services')->insert($services);
        DB::table('actes')->insert($actes);
    }
}
