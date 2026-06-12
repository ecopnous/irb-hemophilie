<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groupe_examens', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignIdFor(Service::class)->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('acte_groupe_examen', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Acte::class)->constrained()->cascadeOnDelete();
            $table->foreignId('groupe_examen_id')->constrained('groupe_examens')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['acte_id', 'groupe_examen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acte_groupe_examen');
        Schema::dropIfExists('groupe_examens');
    }
};
