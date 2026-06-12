<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Assurance;
use App\Models\Configs\Departement;
use App\Models\Configs\Hopital;
use App\Models\Configs\Projet;
use App\Models\Configs\Service;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\liaison\ActeConsultation;
use App\Models\liaison\Image;
use App\Models\other\Symptome;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->enum('type', ['visite', 'examen']);
            $table->enum('type_visite', ['hémophilie', 'drépanocytose', 'standard'])->default('standard');
            $table->boolean('is_project_period')->default(false);

            // consultation
            $table->longText('symptomes')->nullable();
            $table->longText('examen_clinique')->nullable();
            $table->longText('diagnostic_presomption')->nullable();
            $table->longText('complement_anamnese')->nullable();
            $table->longText('plan_traitement_conduite')->nullable();

            // prelevement
            $table->integer('poids')->nullable(); // *
            $table->integer('temperature')->nullable(); // *
            $table->integer('systolite')->nullable();
            $table->integer('taille')->nullable();
            $table->integer('perimetre_cranien')->nullable();
            $table->integer('perimetre_brachial')->nullable();
            $table->integer('frequence_cardiaque')->nullable();
            $table->integer('frequence_respiratoire')->nullable();
            $table->integer('diastolique')->nullable();
            $table->integer('saturation_oxygene')->nullable();
            $table->integer('glycemie')->nullable();
            $table->string('mois')->nullable();
            $table->enum('issue', ['ambulatoire', 'hospitalisation', 'suivi_medical', 'transfert', 'deces', 'autres'])->nullable();
            $table->text('autre_issue')->nullable();
            $table->text('cause_issue')->nullable();

            // lien vers le patient
            $table->foreignIdFor(DossierPatient::class)->constrained()->cascadeOnDelete();
            // lien vers le departement associer
            $table->foreignIdFor(Departement::class)->constrained()->cascadeOnDelete();
            // lien vers le service lier au departement
            $table->foreignIdFor(Service::class)->nullable()->constrained()->nullOnDelete();
            // lien vers le medecin traitant
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();
            // consultation relier au projet
            $table->foreignIdFor(Projet::class)->nullable()->constrained()->nullOnDelete();
            // prise en charge de la consultation
            $table->foreignIdFor(Assurance::class)->nullable()->constrained()->nullOnDelete();

            $table->boolean('prelevement_effectue')->default(false);
            // lien vers les bons
            $table->foreignId('laboratoire_id')->nullable()->index();
            $table->foreignId('imagerie_id')->nullable()->index();
            $table->foreignId('prescription_id')->nullable()->index();
            $table->foreignId('hospitalisation_id')->nullable()->index();
            $table->foreignId('facturation_id')->nullable()->index();

            $table->boolean('is_clore')->default(false);
            $table->boolean('is_visite_program')->default(false);

            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // membre de l'équipe
        Schema::create('consultation_user', function (Blueprint $table) {
            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
        });

        Schema::create('acte_consultation', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Acte::class)->constrained()->cascadeOnDelete();

            $table->string('ref'); // reference du departement

            $table->decimal('montant', 10, 2)->default(0);
            $table->decimal('prise_en_charge', 4, 2)->default(0);
            $table->boolean("payer")->default(false);
            $table->timestamp('date_paiement')->nullable();

            $table->enum('moyen_paiement', ['carte_bancaire', 'mobile_money', 'paypal', 'espece', 'autres'])->nullable();
            // si moyen de paiement est autres
            $table->string("autre_moyen_paiement")->nullable();

            $table->string("note_clinique")->nullable();
            // info relative au labo
            $table->string("commentaire")->nullable();
            $table->string("resultat", 255)->nullable();

            //info relative a l'imagerie
            $table->string("clinique")->nullable();
            $table->string("protocole")->nullable();
            $table->string("cloture")->nullable();

            $table->boolean('valide')->default(false);

            // corps medical
            $table->foreignIdFor(User::class)->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });

        Schema::create("images", function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("path");

            $table->foreignIdFor(ActeConsultation::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create("consultation_symptome", function (Blueprint $table) {
            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Symptome::class)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultation_symptome');
        Schema::dropIfExists('images');
        Schema::dropIfExists('acte_consultation');
        Schema::dropIfExists('consultation_user');
        Schema::dropIfExists('consultations');
    }
};
