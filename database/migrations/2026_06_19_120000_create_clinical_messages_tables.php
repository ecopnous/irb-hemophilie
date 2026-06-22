<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hopital_id')->constrained('hopitals')->cascadeOnDelete();
            $table->foreignId('dossier_patient_id')->nullable()->constrained('dossier_patients')->nullOnDelete();
            $table->foreignId('consultation_id')->nullable()->constrained('consultations')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('clinical_messages')->nullOnDelete();
            $table->foreignId('sender_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_type')->default('user');
            $table->string('category');
            $table->string('priority')->default('normal');
            $table->string('subject');
            $table->longText('body');
            $table->string('status')->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['hopital_id', 'dossier_patient_id', 'sent_at']);
            $table->index(['sender_id', 'sent_at']);
        });

        Schema::create('clinical_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_message_id')->constrained('clinical_messages')->cascadeOnDelete();
            $table->string('recipient_type');
            $table->unsignedBigInteger('recipient_id')->nullable();
            $table->string('channel')->default('in_app');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('delivery_status')->default('delivered');
            $table->timestamps();

            $table->index(['recipient_type', 'recipient_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_message_recipients');
        Schema::dropIfExists('clinical_messages');
    }
};
