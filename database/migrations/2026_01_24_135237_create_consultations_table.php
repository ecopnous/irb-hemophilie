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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('departement');
            $table->string('service');
            $table->string('membre');
            $table->string('prise_en_charge')->nullable();

            // prelevement
            $table->integer('poids');
            $table->integer('temperature');
            $table->integer('systolite')->nullable();
            $table->integer('taille')->nullable();
            $table->integer('perimetre_cranien')->nullable();
            $table->integer('perimetre_brachial')->nullable();
            $table->integer('frequence_cardiaque')->nullable();
            $table->integer('frequence_respiratoire')->nullable();
            $table->integer('diastolique')->nullable();
            $table->integer('saturation_oxygene')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
