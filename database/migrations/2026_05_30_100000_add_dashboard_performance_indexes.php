<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->index(['hopital_id', 'created_at'], 'consultations_hopital_created_idx');
            $table->index(['hopital_id', 'user_id', 'type'], 'consultations_hopital_user_type_idx');
            $table->index(['hopital_id', 'type', 'is_visite_program'], 'consultations_hopital_type_program_idx');
        });

        Schema::table('dossier_patients', function (Blueprint $table) {
            $table->index(['hopital_id', 'genre'], 'dossier_patients_hopital_genre_idx');
            $table->index(['province_id', 'ville_id', 'commune_id'], 'dossier_patients_location_idx');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex('consultations_hopital_created_idx');
            $table->dropIndex('consultations_hopital_user_type_idx');
            $table->dropIndex('consultations_hopital_type_program_idx');
        });

        Schema::table('dossier_patients', function (Blueprint $table) {
            $table->dropIndex('dossier_patients_hopital_genre_idx');
            $table->dropIndex('dossier_patients_location_idx');
        });
    }
};
