<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Departement;
use App\Models\Configs\PacquetSoin;
use App\Models\Configs\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->string('name');

            $table->boolean('is_delete')->default(false);

            $table->string('description')->nullable();
            // joue le role de chef de departement
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->boolean('is_delete')->default(false);

            $table->string('description')->nullable();

            $table->foreignIdFor(Departement::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('actes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('montant', 10, 2);

            $table->decimal('min', 4, 2)->default(0);
            $table->decimal('max', 4, 2)->default(0);
            $table->boolean('is_delete')->default(false);

            $table->decimal('homme_max', 4, 2)->nullable();
            $table->decimal('homme_min', 4, 2)->nullable();
            $table->decimal('femme_max', 4, 2)->nullable();
            $table->decimal('femme_min', 4, 2)->nullable();

            $table->string('unite')->nullable();

            $table->foreignIdFor(Departement::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Service::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('acte_pacquet_soin', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Acte::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(PacquetSoin::class)->constrained('pacquet_soins')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['acte_id', 'pacquet_soin_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acte_pacquet_soin');
        Schema::dropIfExists('actes');
        Schema::dropIfExists('services');
        Schema::dropIfExists('departements');
    }
};
