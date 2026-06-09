<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            CountrySeeder::class,
            HopitalSeeder::class,
            DepartementSeeder::class,
            TagSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Banzuzi',
            'prenom' => 'Ecopnous',
            'email' => 'papos@gmail.com',
            'date_naissance' => '1990-05-15',
            'nationalite' => 'Congo-kinshasa',
            'role' => 'developper',
            'genre' => 'M',
            'hopital_id' => 1,
            'password' => bcrypt('ecopnous'),
        ]);

        // $this->call([
        //     DossierPatientSeeder::class,
        //     ConsultationSeeder::class,
        // ]);
    }
}
