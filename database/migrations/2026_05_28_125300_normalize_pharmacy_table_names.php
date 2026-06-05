<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('phramacies') && !Schema::hasTable('pharmacies')) {
            Schema::rename('phramacies', 'pharmacies');
        }

        if (Schema::hasTable('medicament_phramacie') && !Schema::hasTable('medicament_pharmacie')) {
            Schema::rename('medicament_phramacie', 'medicament_pharmacie');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pharmacies') && !Schema::hasTable('phramacies')) {
            Schema::rename('pharmacies', 'phramacies');
        }

        // No-op on pivot rollback to avoid typo ambiguity in legacy schemas.
    }
};
