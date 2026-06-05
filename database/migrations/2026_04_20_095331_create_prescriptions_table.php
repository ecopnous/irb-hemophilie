<?php

use App\Models\Configs\Hopital;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\prescription\Medicament;
use App\Models\prescription\Pharmacie;
use App\Models\prescription\Prescription;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medicaments', function (Blueprint $table) {
            $table->id();

            $table->string('reference')->unique();
            $table->string('name');
            $table->string('classe');
            $table->string('fournisseur')->nullable();
            $table->string('fabricant');
            $table->string('pays_provenance')->nullable();

            // denomination commune internationale
            $table->string('dci')->nullable();
            // autorisation de mise sur le marché
            $table->string('amm_numero')->nullable();
            $table->integer('amm_duree_validiter')->nullable(); // nombre d'annee valide
            $table->string('amm_organisme')->nullable();

            // caracteristiques du medicament
            $table->string('forme');
            $table->string('dosage');
            $table->string('conditionnement')->nullable();

            $table->timestamp('amm_date_fin')->nullable(); // date lier à la fin de validité
            $table->timestamp('amm_date')->nullable(); // date de l'enregistrement

            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Consultation::class)->constrained();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DossierPatient::class)->constrained();
            $table->timestamps();
        });

        Schema::create('pharmacies', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->foreignIdFor(Hopital::class)->constrained();
            $table->timestamps();
        });

        Schema::create('medicament_pharmacie', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Pharmacie::class)->constrained();
            $table->foreignIdFor(Medicament::class)->constrained();

            $table->integer('quantiter')->default(0);
            $table->decimal('montant', 10, 2)->default(0);

            $table->timestamps();
        });

        Schema::create('medicament_prescription', function (Blueprint $table) {
            $table->id();
            $table->integer('qte_jour');
            $table->integer('nbr');

            $table->foreignIdFor(Medicament::class)->constrained();
            $table->foreignIdFor(Prescription::class)->constrained();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicament_prescription');
        Schema::dropIfExists('medicament_pharmacie');
        Schema::dropIfExists('pharmacies');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medicaments');
    }
};
