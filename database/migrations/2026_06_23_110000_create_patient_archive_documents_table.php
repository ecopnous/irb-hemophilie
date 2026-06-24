<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_archive_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dossier_patient_id')->constrained('dossier_patients')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hopital_id')->constrained('hopitals')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('autre');
            $table->string('source_establishment')->nullable();
            $table->date('document_date')->nullable();
            $table->string('original_filename');
            $table->string('path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->default(0);
            $table->boolean('is_confidential')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['dossier_patient_id', 'category']);
            $table->index(['hopital_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_archive_documents');
    }
};
