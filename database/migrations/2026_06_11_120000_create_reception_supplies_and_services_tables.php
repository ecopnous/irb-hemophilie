<?php

use App\Models\Configs\Hopital;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reception_supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->string('reference')->nullable();
            $table->string('designation');
            $table->string('category', 40)->default('papeterie');
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

        Schema::create('reception_supply_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reception_supply_id')->constrained('reception_supplies')->cascadeOnDelete();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->enum('movement_type', ['entree', 'sortie', 'ajustement']);
            $table->integer('quantity');
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            $table->string('reference')->nullable();
            $table->text('reason')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reception_supply_id', 'created_at']);
        });

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
            $table->index(['hopital_id', 'category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reception_base_services');
        Schema::dropIfExists('reception_supply_movements');
        Schema::dropIfExists('reception_supplies');
    }
};
