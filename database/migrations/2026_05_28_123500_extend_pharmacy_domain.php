<?php

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('medicaments', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('amm_date');
            $table->integer('stock_min')->default(0)->after('is_active');
            $table->date('expiration_date')->nullable()->after('stock_min');
        });

        Schema::table('pharmacies', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('nom');
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('id');
            $table->enum('status', ['draft', 'served', 'partial', 'cancelled'])->default('draft')->after('dossier_patient_id');
            $table->timestamp('served_at')->nullable()->after('status');
            $table->foreignIdFor(User::class, 'served_by')->nullable()->after('served_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('medicament_prescription', function (Blueprint $table) {
            $table->integer('qte_servie')->default(0)->after('nbr');
            $table->text('posologie')->nullable()->after('qte_servie');
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacie_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->foreignId('medicament_id')->constrained('medicaments')->cascadeOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained('prescriptions')->nullOnDelete();
            $table->foreignIdFor(Consultation::class)->nullable()->constrained()->nullOnDelete();
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'depreciation']);
            $table->integer('quantity');
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_at', 'movement_type']);
            $table->index(['pharmacie_id', 'medicament_id']);
            $table->index(['consultation_id', 'prescription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');

        Schema::table('medicament_prescription', function (Blueprint $table) {
            $table->dropColumn(['qte_servie', 'posologie']);
        });

        Schema::table('prescriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('served_by');
            $table->dropColumn(['reference', 'status', 'served_at']);
        });

        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('medicaments', function (Blueprint $table) {
            $table->dropColumn(['is_active', 'stock_min', 'expiration_date']);
        });
    }
};
