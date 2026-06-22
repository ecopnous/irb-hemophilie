<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_message_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hopital_id')->nullable()->constrained('hopitals')->cascadeOnDelete();
            $table->foreignId('departement_id')->nullable()->constrained('departements')->nullOnDelete();
            $table->string('category');
            $table->string('name');
            $table->string('subject');
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['hopital_id', 'departement_id', 'category', 'is_active']);
        });

        $now = now();

        $templates = [
            [
                'category' => 'suivi',
                'name' => 'Consignes post-consultation',
                'subject' => 'Consignes apres votre consultation',
                'body' => "Bonjour {{patient_prenom}},\n\nSuite a votre consultation du {{date_consultation}}, voici les consignes de suivi transmises par {{medecin}}.\n\n[Precisez ici les consignes therapeutiques, repos, hydratation, signes d alerte, etc.]\n\nN hesitez pas a nous contacter en cas de question.",
                'sort_order' => 10,
            ],
            [
                'category' => 'resultats',
                'name' => 'Resultats de laboratoire disponibles',
                'subject' => 'Vos resultats de laboratoire sont disponibles',
                'body' => "Bonjour {{patient_prenom}},\n\nNous vous informons que vos resultats de laboratoire ont ete valides.\n\nExamens concernes :\n{{examens_labo}}\n\nVotre medecin referent ({{medecin}}) a ete informe. Consultez votre messagerie clinique pour le detail ou prenez rendez-vous pour la suite de la prise en charge.",
                'sort_order' => 20,
            ],
            [
                'category' => 'rappel',
                'name' => 'Rappel de rendez-vous',
                'subject' => 'Rappel de votre rendez-vous',
                'body' => "Bonjour {{patient_prenom}},\n\nNous vous rappelons votre rendez-vous medical.\n\nMerci de vous presenter a l heure convenue. En cas d empechement, contactez la reception de l etablissement.",
                'sort_order' => 30,
            ],
            [
                'category' => 'orientation',
                'name' => 'Orientation vers le laboratoire',
                'subject' => 'Orientation vers le laboratoire',
                'body' => "Bonjour {{patient_prenom}},\n\nVotre medecin ({{medecin}}) vous oriente vers le laboratoire pour la realisation d examens complementaires.\n\nPresentez-vous au laboratoire avec ce message et votre dossier patient ({{patient_nin}}).",
                'sort_order' => 40,
            ],
            [
                'category' => 'suivi',
                'name' => 'Suivi hemophilie — consignes',
                'subject' => 'Consignes de suivi hemophilie',
                'body' => "Bonjour {{patient_prenom}},\n\nDans le cadre de votre suivi hemophilie, veuillez respecter les consignes suivantes :\n\n- Surveiller l apparition de nouveaux saignements\n- Respecter la prophylaxie prescrite\n- Conserver votre carnet de traitement a jour\n\nContactez l equipe en urgence en cas de saignement important ou persistant.",
                'sort_order' => 50,
            ],
        ];

        foreach ($templates as $template) {
            DB::table('clinical_message_templates')->insert([
                'hopital_id' => null,
                'departement_id' => null,
                'category' => $template['category'],
                'name' => $template['name'],
                'subject' => $template['subject'],
                'body' => $template['body'],
                'is_active' => true,
                'sort_order' => $template['sort_order'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_message_templates');
    }
};
