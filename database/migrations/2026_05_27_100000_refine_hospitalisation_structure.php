<?php

use App\Models\Configs\Hopital;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosp_services', function (Blueprint $table) {
            $table->foreignIdFor(Hopital::class)->nullable()->after('id')->constrained()->nullOnDelete();
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('departement_id');
        });

        Schema::table('chambres', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('name');
            $table->text('description')->nullable()->after('unite');
            $table->boolean('is_active')->default(true)->after('hosp_service_id');
        });

        Schema::table('lits', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('name');
            $table->text('description')->nullable()->after('reference');
            $table->enum('statut', ['disponible', 'occupe', 'maintenance', 'hors_service'])->default('disponible')->after('description');
            $table->boolean('is_active')->default(true)->after('chambre_id');
        });

        Schema::table('hospitalisations', function (Blueprint $table) {
            $table->foreignId('hosp_service_id')->nullable()->after('departement_id')->constrained('hosp_services')->nullOnDelete();
            $table->enum('statut', ['en_attente', 'active', 'terminee', 'annulee'])->default('active')->after('hopital_id');
            $table->text('motif')->nullable()->after('date_paiement');
            $table->text('note_sortie')->nullable()->after('motif');
        });

        $fallbackHopitalId = DB::table('hopitals')->value('id');

        if ($fallbackHopitalId) {
            DB::table('hosp_services')
                ->whereNull('hopital_id')
                ->update(['hopital_id' => $fallbackHopitalId]);
        }

        $hospitalisations = DB::table('hospitalisations')
            ->select('hospitalisations.id', 'chambres.hosp_service_id')
            ->leftJoin('chambres', 'chambres.id', '=', 'hospitalisations.chambre_id')
            ->get();

        foreach ($hospitalisations as $hospitalisation) {
            DB::table('hospitalisations')
                ->where('id', $hospitalisation->id)
                ->update([
                    'hosp_service_id' => $hospitalisation->hosp_service_id,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('hospitalisations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hosp_service_id');
            $table->dropColumn(['statut', 'motif', 'note_sortie']);
        });

        Schema::table('lits', function (Blueprint $table) {
            $table->dropColumn(['reference', 'description', 'statut', 'is_active']);
        });

        Schema::table('chambres', function (Blueprint $table) {
            $table->dropColumn(['reference', 'description', 'is_active']);
        });

        Schema::table('hosp_services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hopital_id');
            $table->dropColumn(['description', 'is_active']);
        });
    }
};
