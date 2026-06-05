<?php

use App\Models\Configs\Acte;
use App\Models\Consultation;
use App\Models\DossierPatient;
use App\Models\facturation\Facturation;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('facturations', function (Blueprint $table) {
            $table->decimal('total_amount', 15, 2)->default(0)->after('hopital_id');
            $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            $table->string('currency', 3)->default('USD')->after('due_amount');
            $table->string('status')->default('en_attente')->after('currency');
            $table->foreignIdFor(User::class, 'created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->index(['dossier_patient_id', 'created_at']);
        });

        Schema::table('actes', function (Blueprint $table) {
            $table->string('code')->nullable()->after('id');
            $table->decimal('base_price', 10, 2)->nullable()->after('montant');
            $table->boolean('is_active')->default(true)->after('is_delete');
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->after('service_id')->constrained('users')->nullOnDelete();
            $table->index(['code', 'is_active']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Facturation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(DossierPatient::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Acte::class)->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->enum('payment_mode', ['cash', 'mobile_money', 'carte', 'virement', 'autre'])->default('cash');
            $table->string('reference')->nullable();
            $table->timestamp('paid_at');
            $table->text('comment')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignIdFor(User::class, 'voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['paid_at', 'payment_mode']);
            $table->index(['dossier_patient_id', 'facturation_id']);
            $table->index(['created_by', 'voided_at']);
        });

        Schema::create('cash_operations', function (Blueprint $table) {
            $table->id();
            $table->enum('operation_type', ['ouverture', 'cloture', 'entree_manuelle', 'sortie_manuelle', 'ajustement']);
            $table->enum('event_type', ['cash_in', 'cash_out']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->dateTime('performed_at');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason')->nullable();
            $table->foreignIdFor(User::class, 'voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['performed_at', 'operation_type']);
            $table->index(['created_by', 'voided_at']);
        });

        Schema::create('cash_register_events', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->enum('event_type', ['cash_in', 'cash_out']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('USD');
            $table->foreignIdFor(User::class, 'performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('performed_at');
            $table->text('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('performed_at');
            $table->index('performed_by');
        });

        Schema::create('medical_act_price_history', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Acte::class)->constrained()->cascadeOnDelete();
            $table->decimal('old_price', 10, 2)->nullable();
            $table->decimal('new_price', 10, 2);
            $table->foreignIdFor(User::class, 'changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['acte_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_act_price_history');
        Schema::dropIfExists('cash_register_events');
        Schema::dropIfExists('cash_operations');
        Schema::dropIfExists('payments');

        Schema::table('actes', function (Blueprint $table) {
            $table->dropIndex(['code', 'is_active']);
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['code', 'base_price', 'is_active']);
        });

        Schema::table('facturations', function (Blueprint $table) {
            $table->dropIndex(['dossier_patient_id', 'created_at']);
            $table->dropConstrainedForeignId('created_by');
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['total_amount', 'paid_amount', 'due_amount', 'currency', 'status']);
        });
    }
};
