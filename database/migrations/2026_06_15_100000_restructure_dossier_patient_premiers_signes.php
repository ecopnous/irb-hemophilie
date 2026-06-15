<?php

use App\Models\DossierPatient;
use App\Models\DossierPatientPremierSigne;
use App\Models\PremierSigneDefinition;
use Database\Seeders\PremierSigneDefinitionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  private const LEGACY_COLUMNS = [
    'syndrome_mains_pieds',
    'fievre',
    'itere',
    'cvo',
    'transfusion',
    'nbr_transfusion',
    'episodes_epistaxis',
    'nbr_cvo_an',
  ];

  public function up(): void
  {
    Schema::create('premier_signe_definitions', function (Blueprint $table) {
      $table->id();
      $table->string('key')->unique();
      $table->string('label');
      $table->string('description')->nullable();
      $table->string('value_type', 20)->default('age');
      $table->string('value_label')->default('Âge');
      $table->unsignedSmallInteger('sort_order')->default(0);
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });

    Schema::create('dossier_patient_premier_signes', function (Blueprint $table) {
      $table->id();
      $table->foreignIdFor(DossierPatient::class)->constrained()->cascadeOnDelete();
      $table->foreignIdFor(PremierSigneDefinition::class)->constrained()->cascadeOnDelete();
      $table->boolean('present')->nullable();
      $table->unsignedSmallInteger('value')->nullable();
      $table->text('comment')->nullable();
      $table->timestamps();

      $table->unique(['dossier_patient_id', 'premier_signe_definition_id'], 'patient_premier_signe_unique');
    });

    (new PremierSigneDefinitionSeeder())->run();

    $definitions = PremierSigneDefinition::query()->pluck('id', 'key');

    DossierPatient::query()->select(array_merge(['id'], self::LEGACY_COLUMNS))->chunkById(100, function ($patients) use ($definitions) {
      foreach ($patients as $patient) {
        foreach (self::LEGACY_COLUMNS as $column) {
          $legacyValue = $patient->{$column};

          if ($legacyValue === null) {
            continue;
          }

          $definitionId = $definitions[$column] ?? null;

          if (! $definitionId) {
            continue;
          }

          DossierPatientPremierSigne::query()->create([
            'dossier_patient_id' => $patient->id,
            'premier_signe_definition_id' => $definitionId,
            'present' => true,
            'value' => (int) $legacyValue,
            'comment' => null,
          ]);
        }
      }
    });

    Schema::table('dossier_patients', function (Blueprint $table) {
      foreach (self::LEGACY_COLUMNS as $column) {
        $table->dropColumn($column);
      }
    });
  }

  public function down(): void
  {
    Schema::table('dossier_patients', function (Blueprint $table) {
      $table->integer('syndrome_mains_pieds')->nullable();
      $table->integer('fievre')->nullable();
      $table->integer('itere')->nullable();
      $table->integer('cvo')->nullable();
      $table->integer('transfusion')->nullable();
      $table->integer('nbr_transfusion')->nullable();
      $table->integer('episodes_epistaxis')->nullable();
      $table->integer('nbr_cvo_an')->nullable();
    });

    $definitions = PremierSigneDefinition::query()->pluck('key', 'id');

    DossierPatientPremierSigne::query()
      ->with('definition:id,key')
      ->chunkById(200, function ($signes) use ($definitions) {
        foreach ($signes as $signe) {
          $column = $signe->definition?->key;

          if (! $column || ! in_array($column, self::LEGACY_COLUMNS, true)) {
            continue;
          }

          if ($signe->present && $signe->value !== null) {
            DB::table('dossier_patients')
              ->where('id', $signe->dossier_patient_id)
              ->update([$column => $signe->value]);
          }
        }
      });

    Schema::dropIfExists('dossier_patient_premier_signes');
    Schema::dropIfExists('premier_signe_definitions');
  }
};
