<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rendez_vous', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('users')->cascadeOnDelete();
            $table->string('patient_name');
            $table->dateTime('date_rendez_vous');
            $table->boolean('rappel_48h_envoye')->default(false);
            $table->timestamps();

            $table->index(['date_rendez_vous', 'rappel_48h_envoye']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rendez_vous');
    }
};
