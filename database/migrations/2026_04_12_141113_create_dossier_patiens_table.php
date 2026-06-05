<?php

use App\Models\Allergy;
use App\Models\Configs\Assurance;
use App\Models\Configs\Categorisation;
use App\Models\Configs\Hopital;
use App\Models\DossierPatient;
use App\Models\Localisations\Commune;
use App\Models\Localisations\Country;
use App\Models\Localisations\Province;
use App\Models\Localisations\Ville;
use App\Models\other\Tag;
use App\Models\QuestionsFiche;
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
        Schema::create('dossier_patients', function (Blueprint $table) {
            $table->id();
            $table->string("nin")->unique();

            // Informations personnelles
            $table->string('photo')->nullable();
            $table->string('nom')->nullable();
            $table->string('postnom')->nullable();
            $table->string('prenom');
            $table->enum('genre', ['M', 'F']);
            $table->enum('etat_civil', ['Célibataire', 'Marié', 'Divorcé', 'Veu(f)ve'])->default('Célibataire');
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->date('date_naissance')->nullable();

            // Informations régionales
            $table->string('quartier')->nullable();
            $table->string('avenue')->nullable();
            $table->string('num_habitation')->nullable();

            // Autres informations
            $table->string('ins')->nullable();
            $table->text('note')->nullable();

            //Informations complementaire
            $table->string("nom_pere")->nullable();
            $table->string("province_pere")->nullable();
            $table->string("tribut_pere")->nullable();
            $table->string("profession_pere")->nullable();

            $table->string("nom_mere")->nullable();
            $table->string("province_mere")->nullable();
            $table->string("tribut_mere")->nullable();
            $table->string("profession_mere")->nullable();

            $table->string("poids_naissance")->nullable();
            $table->string("type_famille")->nullable();
            $table->integer("rang_fratrie")->nullable();
            $table->integer("nb_freres")->nullable();
            $table->integer("nb_soeurs")->nullable();
            $table->integer("deces_freres")->nullable();
            $table->integer("deces_soeurs")->nullable();
            $table->text("histoire_famille_supplementaire")->nullable();

            $table->boolean("is_hemophile")->default(false);
            $table->boolean("is_anemique")->default(false);
            $table->boolean("is_dead")->default(false);

            // histoire personnelle
            $table->integer("age_gestationnel")->nullable();
            $table->boolean("allaitement_maternel")->default(false);
            $table->boolean("med_traditionnel")->default(false);
            $table->boolean("moringa_oleifera")->default(false);
            $table->string("indications")->nullable();
            $table->string("duree_prise")->nullable();
            $table->text("vaccins")->nullable();
            $table->text("histoire_perso_supplementaire")->nullable();

            // histoire de la maladie
            $table->integer("syndrome_mains_pieds")->nullable();
            $table->integer("fievre")->nullable();
            $table->integer("itere")->nullable();
            $table->integer("cvo")->nullable();
            $table->integer("transfusion")->nullable();
            $table->integer("nbr_transfusion")->nullable();
            $table->integer("episodes_epistaxis")->nullable();
            $table->integer("nbr_cvo_an")->nullable();
            $table->text("premier_signes_supplementaires")->nullable();

            // autres antecedents
            $table->string("antecedents_medicales")->nullable();
            $table->string("antecedents_chirurgicaux")->nullable();
            $table->string("antecedents_familiaux")->nullable();
            $table->string("antecedents_obstetricaux")->nullable();
            $table->string("antecedents_gynocola")->nullable();
            $table->string("antecedents_neurologiques")->nullable();
            $table->string("antecedents_cardiovasculaires")->nullable();
            $table->string("antecedents_digestifs")->nullable();
            $table->string("antecedents_endocrinologiques")->nullable();
            $table->string("antecedents_hematologiques")->nullable();
            $table->text("antecedents_supplementaires")->nullable();


            $table->foreignIdFor(Province::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Ville::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Commune::class)->nullable()->constrained()->nullOnDelete();
            $table->text("adresses_supplementaires")->nullable();


            $table->foreignIdFor(Assurance::class)->nullable()->constrained()->nullOnDelete();
            // pays de naissance
            $table->foreignIdFor(Country::class)->nullable()->constrained();
            // liaison parentale
            $table->foreignIdFor(DossierPatient::class)->nullable()->constrained()->nullOnDelete();
            // l'utilisateur qui a créé le dossier
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(Categorisation::class)->nullable()->nullOnDelete();
            // l'hôpital auquel le patient est rattaché
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create("dossier_patient_tag", function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Tag::class)->constrained();
            $table->foreignIdFor(DossierPatient::class)->constrained();
        });

        Schema::create('allergies', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['medicament', 'alimentaire', 'environnementale', 'animaux', 'autre'])->default('medicament');
            $table->string('autre')->nullable();
            $table->string('symptome');
            $table->string('solution');
            $table->string('description')->nullable();
            $table->timestamp('date_debut');
            $table->timestamp('date_fin')->nullable();

            $table->foreignIdFor(DossierPatient::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('allergie_dossier_patient', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DossierPatient::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Allergy::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('questions_fiches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('dossier_patients_questions_fiches', function (Blueprint $table) {
            $table->id();
            $table->boolean('enable')->default(false);
            $table->string('value')->nullable();
            $table->string('obs')->nullable();
            $table->foreignIdFor(QuestionsFiche::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DossierPatient::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allergie_dossier_patient');
        Schema::dropIfExists('allergies');
        Schema::dropIfExists('dossier_patient_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('dossier_patients');
    }
};
