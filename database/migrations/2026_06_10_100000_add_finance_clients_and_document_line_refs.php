<?php

use App\Models\Configs\Hopital;
use App\Models\Configs\Service;
use App\Models\LaboratoryConsumable;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_clients', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['particulier', 'entreprise'])->default('particulier');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('tax_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['hopital_id', 'name']);
            $table->index(['hopital_id', 'is_active']);
        });

        Schema::table('finance_documents', function (Blueprint $table) {
            $table->enum('beneficiary_type', ['patient', 'client'])->default('patient')->after('dossier_patient_id');
            $table->foreignId('finance_client_id')->nullable()->after('beneficiary_type')->constrained('finance_clients')->nullOnDelete();
        });

        Schema::table('finance_document_items', function (Blueprint $table) {
            $table->foreignIdFor(Service::class)->nullable()->after('acte_id')->constrained()->nullOnDelete();
            $table->foreignIdFor(LaboratoryConsumable::class)->nullable()->after('service_id')->constrained('laboratory_consumables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('finance_document_items', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropForeign(['laboratory_consumable_id']);
            $table->dropColumn(['service_id', 'laboratory_consumable_id']);
        });

        Schema::table('finance_documents', function (Blueprint $table) {
            $table->dropForeign(['finance_client_id']);
            $table->dropColumn(['beneficiary_type', 'finance_client_id']);
        });

        Schema::dropIfExists('finance_clients');
    }
};
