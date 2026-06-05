<?php

use App\Models\facturation\Payment;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Payment::class)->constrained()->cascadeOnDelete();
            $table->string('action'); // created, corrected, voided
            $table->decimal('old_amount', 15, 2)->nullable();
            $table->decimal('new_amount', 15, 2)->nullable();
            $table->json('changes')->nullable();
            $table->foreignIdFor(User::class, 'performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('performed_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['payment_id', 'performed_at']);
            $table->index(['action', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_audits');
    }
};
