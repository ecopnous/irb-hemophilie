<?php

use App\Models\Configs\Hopital;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reception_base_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('designation');
            $table->string('category', 40)->default('nettoyage');
            $table->string('unit', 60)->default('pce');
            $table->unsignedInteger('planned_stock')->default(0);
            $table->unsignedInteger('current_stock')->default(0);
            $table->unsignedInteger('stock_min')->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['hopital_id', 'designation']);
            $table->index(['hopital_id', 'category', 'is_active']);
        });

        Schema::create('reception_base_supply_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_base_supply_id')->constrained('reception_base_supplies')->cascadeOnDelete();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->enum('movement_type', ['entree', 'sortie', 'ajustement']);
            $table->integer('quantity');
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            $table->string('reference')->nullable();
            $table->text('reason')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reception_base_supply_id', 'created_at']);
        });

        if (Schema::hasTable('reception_supplies')) {
            $hygieneRows = DB::table('reception_supplies')
                ->whereIn('category', ['hygiene', 'menager', 'nettoyage', 'entretien'])
                ->get();

            foreach ($hygieneRows as $row) {
                $exists = DB::table('reception_base_supplies')
                    ->where('hopital_id', $row->hopital_id)
                    ->where('designation', $row->designation)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('reception_base_supplies')->insert([
                    'hopital_id' => $row->hopital_id,
                    'reference' => $row->reference,
                    'designation' => $row->designation,
                    'category' => in_array($row->category, ['hygiene', 'menager', 'nettoyage', 'entretien', 'consommable'], true)
                        ? $row->category
                        : 'nettoyage',
                    'unit' => $row->unit,
                    'planned_stock' => $row->planned_stock,
                    'current_stock' => $row->current_stock,
                    'stock_min' => $row->stock_min,
                    'notes' => $row->notes,
                    'is_active' => $row->is_active,
                    'updated_by' => $row->updated_by,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);
            }

            DB::table('reception_supplies')
                ->whereIn('category', ['hygiene', 'menager', 'nettoyage', 'entretien'])
                ->delete();
        }

        Schema::dropIfExists('reception_base_services');
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_base_supply_movements');
        Schema::dropIfExists('reception_base_supplies');

        Schema::create('reception_base_services', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('category', 40)->default('accueil');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->foreignIdFor(User::class, 'updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['hopital_id', 'name']);
        });
    }
};
