<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            if (!Schema::hasColumn('consultations', 'prescription_medicale')) {
                $table->longText('prescription_medicale')->nullable()->after('plan_traitement_conduite');
            }

            if (!Schema::hasColumn('consultations', 'rendez_vous_medical')) {
                $table->longText('rendez_vous_medical')->nullable()->after('prescription_medicale');
            }
        });
    }

    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            if (Schema::hasColumn('consultations', 'rendez_vous_medical')) {
                $table->dropColumn('rendez_vous_medical');
            }

            if (Schema::hasColumn('consultations', 'prescription_medicale')) {
                $table->dropColumn('prescription_medicale');
            }
        });
    }
};
