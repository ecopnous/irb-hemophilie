<?php

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
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('native_name')->nullable();
            $table->string('code', 3);
            $table->string('color')->nullable();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3);
        });

        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(Country::class)->constrained()->cascadeOnDelete();
        });

        Schema::create('villes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(Province::class)->constrained()->cascadeOnDelete();
        });

        Schema::create('communes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(Ville::class)->constrained()->cascadeOnDelete();
        });

        Schema::create('zone_santes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(Commune::class)->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zone_santes');
        Schema::dropIfExists('communes');
        Schema::dropIfExists('villes');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('languages');
    }
};
