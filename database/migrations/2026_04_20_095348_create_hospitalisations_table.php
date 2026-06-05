<?php

use App\Models\Configs\Departement;
use App\Models\Configs\Hopital;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\hospitalisation\Chambre;
use App\Models\hospitalisation\HospService;
use App\Models\hospitalisation\Lit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hosp_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->foreignIdFor(Departement::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('chambres', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['standard', 'vip', 'privee'])->default('standard');
            $table->decimal('montant', 10, 2)->default(0);
            $table->enum('unite', ['jour', 'semaine', 'mois', 'annee'])->default('jour');

            $table->foreignIdFor(HospService::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('lits', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->foreignIdFor(Chambre::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('hospitalisations', function (Blueprint $table) {
            $table->id();
            $table->decimal('montant', 10, 2)->default(0);
            $table->boolean('payer')->default(false);
            $table->enum('unite', ['jour', 'semaine', 'mois', 'annee'])->default('jour');
            $table->enum('moyen_paiement', ['carte_bancaire', 'mobile_money', 'paypal', 'espece', 'autres'])->nullable();
            $table->string("autre_moyen_paiement")->nullable();

            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DossierPatient::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Departement::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Chambre::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Lit::class)->constrained()->cascadeOnDelete();

            $table->timestamp('date_entree');
            $table->timestamp('date_sortie')->nullable();
            $table->timestamp('date_paiement')->nullable();

            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hospitalisations');
        Schema::dropIfExists('lits');
        Schema::dropIfExists('chambres');
        Schema::dropIfExists('hosp_services');
    }
};
