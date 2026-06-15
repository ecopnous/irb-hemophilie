<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->index(['dossier_patient_id', 'created_at'], 'consultations_patient_created_idx');
            $table->index(['dossier_patient_id', 'type'], 'consultations_patient_type_idx');
            $table->index(['dossier_patient_id', 'projet_id', 'is_project_period'], 'consultations_patient_project_period_idx');
            $table->index(['hopital_id', 'reference'], 'consultations_hopital_reference_idx');
            $table->index(['hopital_id', 'laboratoire_id'], 'consultations_hopital_labo_idx');
            $table->index(['hopital_id', 'facturation_id'], 'consultations_hopital_facturation_idx');
        });

        Schema::table('dossier_patients', function (Blueprint $table) {
            $table->index(['hopital_id', 'created_at'], 'dossier_patients_hopital_created_idx');
            $table->index(['hopital_id', 'ins'], 'dossier_patients_hopital_ins_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['hopital_id', 'grade'], 'users_hopital_grade_idx');
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropIndex('consultations_patient_created_idx');
            $table->dropIndex('consultations_patient_type_idx');
            $table->dropIndex('consultations_patient_project_period_idx');
            $table->dropIndex('consultations_hopital_reference_idx');
            $table->dropIndex('consultations_hopital_labo_idx');
            $table->dropIndex('consultations_hopital_facturation_idx');
        });

        Schema::table('dossier_patients', function (Blueprint $table) {
            $table->dropIndex('dossier_patients_hopital_created_idx');
            $table->dropIndex('dossier_patients_hopital_ins_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_hopital_grade_idx');
        });
    }
};
