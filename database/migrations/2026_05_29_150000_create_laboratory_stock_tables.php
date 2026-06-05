<?php

use App\Models\Configs\Hopital;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('laboratory_consumables', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('reference')->nullable();
            $table->string('category', 60)->default('reactif');
            $table->string('unit', 40)->default('unite');
            $table->integer('current_stock')->default(0);
            $table->integer('stock_min')->default(0);
            $table->string('storage_condition')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hopital_id', 'name']);
            $table->index(['hopital_id', 'category', 'is_active']);
            $table->index(['current_stock', 'stock_min']);
        });

        Schema::create('laboratory_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_consumable_id')->constrained('laboratory_consumables')->cascadeOnDelete();
            $table->enum('movement_type', ['in', 'out', 'adjustment', 'loss', 'expired', 'transfer']);
            $table->integer('quantity');
            $table->integer('quantity_before')->default(0);
            $table->integer('quantity_after')->default(0);
            $table->string('reference')->nullable();
            $table->string('lot_number')->nullable();
            $table->date('expiration_date')->nullable();
            $table->string('destination')->nullable();
            $table->text('reason')->nullable();
            $table->foreignIdFor(User::class, 'created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_at', 'movement_type']);
            $table->index(['laboratory_consumable_id', 'created_at']);
            $table->index(['lot_number', 'expiration_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_stock_movements');
        Schema::dropIfExists('laboratory_consumables');
    }
};
