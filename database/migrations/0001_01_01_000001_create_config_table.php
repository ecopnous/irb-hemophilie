<?php

use App\Models\Configs\Categorisation;
use App\Models\Localisations\Commune;
use App\Models\Localisations\Country;
use App\Models\Localisations\Province;
use App\Models\Localisations\Ville;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hopitals', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('name');
            $table->enum('type', ['public', 'privée', 'clinique'])->default('public');
            $table->enum('devise', ['cdf', 'usd', 'eur'])->default('cdf');
            $table->string('code_postal');

            $table->boolean('is_actif')->default(true);
            $table->boolean('is_delete')->default(false);
            $table->enum('forfait', ['basic', 'premium'])->default('basic');

            $table->string('site_web')->nullable();
            $table->string('numero_licence')->nullable();
            $table->string('autorite_regulation')->nullable();
            $table->string('description')->nullable();
            $table->string('quartier');
            $table->string('avenue');
            $table->string('numero');

            $table->foreignIdFor(Country::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Province::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Ville::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignIdFor(Commune::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('groupe_hopitals', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('objetif');
            $table->string('note')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('groupe_hopital_hopital', function (Blueprint $table) {
            $table->id();
            $table->foreignId('groupe_hopital_id')->constrained()->onDelete('cascade');
            $table->foreignId('hopital_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('categorisations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('pourcentage', 5, 2)->default(0);

            $table->string('description')->nullable();

            // categorie parent
            $table->timestamps();
        });

        Schema::create('pacquet_soins', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->boolean('paiement_directe')->default(false);
            $table->boolean('is_delete')->default(false);

            $table->string('description')->nullable();

            // categorie du pacquet de soin
            $table->foreignIdFor(Categorisation::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('projets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('reference');

            $table->boolean('is_delete')->default(false);

            $table->string('description')->nullable();

            $table->timestamps();
        });

        Schema::create('carnet_vaccins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description');
            $table->string('age_initial');

            $table->boolean('is_delete')->default(false);

            $table->string('age_operator')->nullable();
            $table->string('age_terminal')->nullable();

            $table->timestamps();
        });

        Schema::create('assurances', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->string('name');
            $table->enum('type', ['assurance', 'entreprise', 'organisation', 'partenaire', 'particulier'])->default('assurance');

            $table->boolean('is_delete')->default(false);

            $table->string('email')->nullable();
            $table->string('logo')->nullable();
            $table->string('description')->nullable();

            // lien vers la categorie associer
            $table->foreignIdFor(Categorisation::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->string("tag");
        });

        Schema::create('symptomes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
        });

        Schema::create('questions_cliniques', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('enabled')->default(false);
            $table->string('note')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('symptomes');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('assurances');

        Schema::dropIfExists('carnet_vaccins');
        Schema::dropIfExists('projets');
        Schema::dropIfExists('pacquet_soins');
        Schema::dropIfExists('categorisations');
        Schema::dropIfExists('groupe_hopital_hopital');
        Schema::dropIfExists('groupe_hopitals');
        Schema::dropIfExists('hopitals');
    }
};
