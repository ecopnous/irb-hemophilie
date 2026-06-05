<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // État général / Priorité
            ['name' => 'Patient standard', 'tag' => 'standard', 'id' => 1],
            ['name' => 'Patient à risque', 'tag' => 'à risque', 'id' => 2],
            ['name' => 'Patient urgent / Critique', 'tag' => 'critique', 'id' => 3],

            // Conditions Sanguines
            ['name' => 'Patient hémophile', 'tag' => 'hémophile', 'id' => 4],
            ['name' => 'Patient drépanocytaire', 'tag' => 'drépanocytaire', 'id' => 5],
            ['name' => 'Patient avec trouble de la coagulation', 'tag' => 'trouble coagulation', 'id' => 6],

            // Maladies Chroniques
            ['name' => 'Patient diabétique', 'tag' => 'diabétique', 'id' => 7],
            ['name' => 'Patient hypertendu', 'tag' => 'hypertendu', 'id' => 8],
            ['name' => 'Patient asthmatique', 'tag' => 'asthmatique', 'id' => 9],

            // Autres spécificités
            ['name' => 'Patient allergique', 'tag' => 'allergique', 'id' => 10],
            ['name' => 'Patient immunodéprimé', 'tag' => 'immunodéprimé', 'id' => 11],
            ['name' => 'Patient handicapé (PMR)', 'tag' => 'handicapé', 'id' => 12],
            ['name' => 'Patient male nourri', 'tag' => 'male_nutri', 'id' => 13],
        ];

        $symptomes = [
            ['name' => 'Fievre'],
            ['name' => 'Frissons'],
            ['name' => 'Courbatures'],
            ['name' => 'Uries foncées'],
            ['name' => 'Infection'],
            ['name' => 'Douleur'],
            ['name' => 'Toux'],
            ['name' => 'Diarrhee'],
            ['name' => 'Vomissement'],
            ['name' => 'Nausées'],
            ['name' => 'Diarrhée'],
            ['name' => 'Grosesse'],
            ['name' => 'Fatigue'],
            ['name' => 'Maux de tete'],
            ['name' => 'Difficulte respiratoire'],
            ['name' => 'Autre'],
        ];

        DB::table('tags')->insert($tags);
        DB::table('symptomes')->insert($symptomes);
    }
}
