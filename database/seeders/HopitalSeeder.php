<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HopitalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $hopitals = [
            [
                'id' => 1,
                'reference' => 'G-00001',
                'name' => 'Administration Generale',
                'type' => 'privée',
                'devise' => 'cdf',
                'code_postal' => '012',
                'is_actif' => true,
                'is_delete' => false,
                'forfait' => 'premium',
                'site_web' => null,
                'numero_licence' => null,
                'autorite_regulation' => null,
                'description' => 'Hopital principal de demonstration',
                'quartier' => 'Gombe',
                'avenue' => 'Boulevard du 30 Juin',
                'numero' => '1',
                'country_id' => 52,
                'province_id' => 10,
                'ville_id' => 1,
                'commune_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('hopitals')->insert($hopitals);
    }
}
