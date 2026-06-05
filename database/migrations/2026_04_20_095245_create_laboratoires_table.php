<?php

use App\Models\Configs\Hopital;
use App\Models\Consultation;
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
        Schema::create('laboratoires', function (Blueprint $table) {
            $table->id();
            $table->string("note")->nullable();
            $table->string("renseignement");
            $table->string("antibiotique")->nullable();
            $table->enum("statut", ["en attente", "en cours", "terminé", "bloqué"])->default("en attente");

            // information du prelement
            $table->timestamp('date_heure_prelevemnt')->nullable();
            $table->timestamp('date_heure_validation')->nullable();
            $table->string("commentaire")->nullable();

            // lien du valideur
            $table->foreignId('user_valideur_id')->nullable()
                ->constrained('users')->restrictOnDelete();

            // Lien vers la consultation concerner
            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            // lien du preleveur
            $table->foreignIdFor(User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratoires');
    }
};
