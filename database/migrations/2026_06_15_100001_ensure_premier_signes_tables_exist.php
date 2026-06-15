<?php

use App\Models\PremierSigneDefinition;
use Database\Seeders\PremierSigneDefinitionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('premier_signe_definitions')) {
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
        }

        if (! Schema::hasTable('dossier_patient_premier_signes')) {
            Schema::create('dossier_patient_premier_signes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dossier_patient_id')->constrained('dossier_patients')->cascadeOnDelete();
                $table->foreignId('premier_signe_definition_id')->constrained('premier_signe_definitions')->cascadeOnDelete();
                $table->boolean('present')->nullable();
                $table->unsignedSmallInteger('value')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();

                $table->unique(['dossier_patient_id', 'premier_signe_definition_id'], 'patient_premier_signe_unique');
            });
        }

        if (PremierSigneDefinition::query()->count() === 0) {
            (new PremierSigneDefinitionSeeder())->run();
        }
    }

    public function down(): void
    {
        // Réparation uniquement — pas de rollback destructif.
    }
};
