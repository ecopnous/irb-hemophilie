<?php

use App\Models\Configs\Departement;
use App\Models\Configs\Service;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('inventory_locations', function (Blueprint $table) {
            $table->foreignIdFor(Departement::class)
                ->nullable()
                ->after('floor')
                ->constrained()
                ->nullOnDelete();

            $table->foreignIdFor(Service::class)
                ->nullable()
                ->after('departement_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('inventory_locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropConstrainedForeignId('departement_id');
        });
    }
};
