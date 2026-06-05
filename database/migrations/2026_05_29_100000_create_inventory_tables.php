<?php

use App\Models\Configs\Hopital;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40)->nullable();
            $table->string('type', 40)->default('espace_libre');
            $table->string('building')->nullable();
            $table->string('floor')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hopital_id', 'name']);
            $table->index(['hopital_id', 'type', 'is_active']);
        });

        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_useful_life_years')->nullable();
            $table->decimal('default_depreciation_rate', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hopital_id', 'name']);
            $table->index(['hopital_id', 'is_active']);
        });

        Schema::create('inventory_services', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40)->nullable();
            $table->string('manager_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['hopital_id', 'name']);
            $table->index(['hopital_id', 'is_active']);
        });

        Schema::create('inventory_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Hopital::class)->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_location_id')->nullable()->constrained('inventory_locations')->nullOnDelete();
            $table->foreignId('inventory_category_id')->nullable()->constrained('inventory_categories')->nullOnDelete();
            $table->foreignId('inventory_service_id')->nullable()->constrained('inventory_services')->nullOnDelete();
            $table->foreignIdFor(User::class, 'assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('inventory_number')->unique();
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->string('reference')->nullable();
            $table->string('serial_number')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('status', 30)->default('en_service');
            $table->text('description')->nullable();
            $table->text('observation')->nullable();

            $table->date('acquired_at')->nullable();
            $table->decimal('acquisition_cost', 15, 2)->default(0);
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->unsignedSmallInteger('useful_life_years')->nullable();
            $table->enum('depreciation_method', ['lineaire', 'degressif'])->default('lineaire');
            $table->decimal('depreciation_rate', 5, 2)->nullable();
            $table->string('currency', 3)->default('USD');

            $table->timestamps();

            $table->index(['hopital_id', 'status']);
            $table->index(['inventory_category_id', 'inventory_service_id']);
            $table->index(['acquired_at', 'depreciation_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_assets');
        Schema::dropIfExists('inventory_services');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('inventory_locations');
    }
};
