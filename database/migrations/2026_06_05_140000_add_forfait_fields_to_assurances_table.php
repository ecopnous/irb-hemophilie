<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assurances', function (Blueprint $table) {
            $table->boolean('forfait_actif')->default(false)->after('categorisation_id');
            $table->decimal('prix_patient', 10, 2)->nullable()->after('forfait_actif');
        });
    }

    public function down(): void
    {
        Schema::table('assurances', function (Blueprint $table) {
            $table->dropColumn(['forfait_actif', 'prix_patient']);
        });
    }
};
