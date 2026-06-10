<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('finance_document_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropForeign(['laboratory_consumable_id']);
        });

        Schema::table('finance_document_items', function (Blueprint $table) {
            $table->dropColumn(['service_id', 'laboratory_consumable_id']);
            $table->foreignId('medicament_id')->nullable()->after('acte_id')->constrained('medicaments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_document_items', function (Blueprint $table) {
            $table->dropForeign(['medicament_id']);
            $table->dropColumn('medicament_id');
        });

        Schema::table('finance_document_items', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('acte_id')->constrained('services')->nullOnDelete();
            $table->foreignId('laboratory_consumable_id')->nullable()->after('service_id')->constrained('laboratory_consumables')->nullOnDelete();
        });
    }
};
