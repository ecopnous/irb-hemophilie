<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string("nin")->unique();

            // Informations personnelles
            $table->string('photo')->nullable();
            $table->string('nom');
            $table->string('postnom')->nullable();
            $table->string('prenom');
            $table->enum('genre', ['M', 'F']);
            $table->enum('etat_civil', ['Célibataire', 'Marié', 'Divorcé'])->default('Célibataire');
            $table->string('telephone')->nullable();
            $table->string('email')->unique()->nullable();
            $table->date('date_naissance');

            // Informations régionales
            $table->string('nationalite');
            $table->string('province');
            $table->string('territoire');
            $table->string('commune');
            $table->string('quartier')->nullable();
            $table->string('avenue');
            $table->string('numero_habitation');

            // Autres informations
            $table->json('langues')->nullable();
            $table->string('type_dossier');
            $table->string('categorisation');
            $table->string('prise_en_charge')->nullable();
            $table->string('ins')->nullable();
            $table->text('note')->nullable();

            //Informations complementaire
            $table->string("grade")->nullable();
            $table->string("unite")->nullable();
            $table->string("matricule")->nullable();
            $table->string("groupe_sang")->nullable();
            $table->string("electrophorese")->nullable();

            $table->string("pere")->nullable();
            $table->string("mere")->nullable();
            $table->string("epoux")->nullable();
            $table->string("personne_contacter")->nullable();
            
            $table->string("ethnie")->nullable();
            $table->string("province_orginine")->nullable();
            $table->string("Race")->nullable();
            
            // Timestamps
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('nom');
            $table->index('province');
            
            $table->string("parent_tuteur")->nullable()->foreignId('patient_id')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
