<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_message_id')->constrained('clinical_messages')->cascadeOnDelete();
            $table->string('original_name');
            $table->string('path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();
        });

        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->foreignId('dossier_patient_id')->nullable()->after('doctor_id')->constrained('dossier_patients')->nullOnDelete();
            $table->boolean('rappel_patient_48h_envoye')->default(false)->after('rappel_48h_envoye');
        });
    }

    public function down(): void
    {
        Schema::table('rendez_vous', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dossier_patient_id');
            $table->dropColumn('rappel_patient_48h_envoye');
        });

        Schema::dropIfExists('clinical_message_attachments');
    }
};
