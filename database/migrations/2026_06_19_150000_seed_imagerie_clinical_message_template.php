<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('clinical_message_templates')
            ->where('name', "Resultats d'imagerie disponibles")
            ->exists();

        if ($exists) {
            return;
        }

        $now = now();

        DB::table('clinical_message_templates')->insert([
            'hopital_id' => null,
            'departement_id' => null,
            'category' => 'resultats',
            'name' => "Resultats d'imagerie disponibles",
            'subject' => 'Votre compte rendu d imagerie est disponible',
            'body' => "Bonjour {{patient_prenom}},\n\nNous vous informons que votre compte rendu d imagerie a ete finalise.\n\nExamens concernes :\n{{examens_imagerie}}\n\nVotre medecin referent ({{medecin}}) a ete informe. Consultez votre messagerie clinique ou prenez rendez-vous pour la suite de la prise en charge.",
            'is_active' => true,
            'sort_order' => 25,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('clinical_message_templates')
            ->where('name', "Resultats d'imagerie disponibles")
            ->delete();
    }
};
