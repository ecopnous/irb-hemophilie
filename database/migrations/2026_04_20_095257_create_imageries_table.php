<?php

use App\Models\Configs\Hopital;
use App\Models\Consultation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('imageries', function (Blueprint $table) {
            $table->id();
            $table->string("renseignement");
            $table->string("antibiotique")->nullable();
            $table->enum("statut", ["en attente", "en cours", "terminé", "bloqué"])->default("en attente");
            $table->string("note")->nullable();
            // Lien vers le medecin traitant
            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imageries');
    }
};
