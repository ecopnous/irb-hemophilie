<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('dossier_patients', function (Blueprint $table) {
            $table->json('fiche_section_status')->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('dossier_patients', function (Blueprint $table) {
            $table->dropColumn('fiche_section_status');
        });
    }
};
