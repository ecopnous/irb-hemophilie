<?php

use App\Models\hospitalisation\Hospitalisation;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('hospitalisations', function (Blueprint $table) {
            if (!Schema::hasColumn('hospitalisations', 'total_amount')) {
                $table->decimal('total_amount', 15, 2)->default(0)->after('montant');
            }
            if (!Schema::hasColumn('hospitalisations', 'paid_amount')) {
                $table->decimal('paid_amount', 15, 2)->default(0)->after('total_amount');
            }
            if (!Schema::hasColumn('hospitalisations', 'due_amount')) {
                $table->decimal('due_amount', 15, 2)->default(0)->after('paid_amount');
            }
            if (!Schema::hasColumn('hospitalisations', 'currency')) {
                $table->string('currency', 3)->default('USD')->after('due_amount');
            }
            if (!Schema::hasColumn('hospitalisations', 'created_by')) {
                $table->foreignIdFor(User::class, 'created_by')->nullable()->after('note_sortie')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('hospitalisations', 'updated_by')) {
                $table->foreignIdFor(User::class, 'updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
        });

        Schema::create('hospitalisation_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hospitalisation::class)->constrained()->cascadeOnDelete();
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

            $table->index(['hospitalisation_id', 'paid_at']);
            $table->index(['created_by', 'voided_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitalisation_payments');

        Schema::table('hospitalisations', function (Blueprint $table) {
            if (Schema::hasColumn('hospitalisations', 'updated_by')) {
                $table->dropConstrainedForeignId('updated_by');
            }
            if (Schema::hasColumn('hospitalisations', 'created_by')) {
                $table->dropConstrainedForeignId('created_by');
            }
            foreach (['total_amount', 'paid_amount', 'due_amount', 'currency'] as $col) {
                if (Schema::hasColumn('hospitalisations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
