<?php

use App\Models\Configs\Departement;
use App\Models\Configs\Hopital;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\facturation\CategoriesCaisse;
use App\Models\facturation\Caisse;
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
        Schema::create('facturations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DossierPatient::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('categories_caisses', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("description")->nullable();
            $table->integer("ordre");
            $table->enum("statut", ["active", "inactive"])->default("active");
            $table->timestamps();
        });

        Schema::create('caisses', function (Blueprint $table) {
            $table->id();
            $table->date('date_reference');
            $table->string('numero_piece');
            $table->string('libelle');      // recette  // depense
            $table->enum('type_operation', ['crédit', 'débit']);
            $table->decimal('montant', 15, 2);
            $table->string('devise');
            $table->boolean('securiter')->default(false);
            $table->foreignIdFor(CategoriesCaisse::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Departement::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('caisse_user', function (Blueprint $table) {
            $table->foreignIdFor(Caisse::class)->constrained()->restrictOnDelete();
            $table->foreignIdFor(User::class)->constrained()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('caisse_user');
        Schema::dropIfExists('caisses');
        Schema::dropIfExists('categories_caisses');
        Schema::dropIfExists('facturations');
    }
};
