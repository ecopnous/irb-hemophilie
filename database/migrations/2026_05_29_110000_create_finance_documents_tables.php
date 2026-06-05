<?php

use App\Models\Configs\Acte;
use App\Models\Configs\Hopital;
use App\Models\DossierPatient;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('finance_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DossierPatient::class)->nullable()->constrained()->nullOnDelete();
            $table->foreignId('source_document_id')->nullable()->constrained('finance_documents')->nullOnDelete();

            $table->enum('document_type', ['devis', 'facture', 'avoir']);
            $table->string('status', 30)->default('draft');
            $table->string('number')->unique();
            $table->date('issue_date');
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();

            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('total_tva', 15, 2)->default(0);
            $table->decimal('total_ttc', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');

            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['hopital_id', 'document_type', 'status']);
            $table->index(['dossier_patient_id', 'issue_date']);
            $table->index(['source_document_id']);
        });

        Schema::create('finance_document_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_document_id')->constrained('finance_documents')->cascadeOnDelete();
            $table->foreignIdFor(Acte::class)->nullable()->constrained()->nullOnDelete();
            $table->enum('line_type', ['acte', 'service', 'produit', 'autre'])->default('service');
            $table->string('designation');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('price_ht', 15, 2)->default(0);
            $table->decimal('tva', 6, 2)->default(0);
            $table->decimal('discount', 6, 2)->default(0);
            $table->decimal('total_ht', 15, 2)->default(0);
            $table->decimal('total_ttc', 15, 2)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['finance_document_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_document_items');
        Schema::dropIfExists('finance_documents');
    }
};
