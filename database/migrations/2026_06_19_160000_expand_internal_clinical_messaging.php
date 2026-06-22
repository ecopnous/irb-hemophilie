<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE clinical_messages MODIFY dossier_patient_id BIGINT UNSIGNED NULL');
        }

        Schema::table('clinical_messages', function (Blueprint $table) {
            $table->foreignId('thread_id')->nullable()->after('id')->index();
            $table->string('message_type')->default('patient')->after('sender_type')->index();
            $table->string('recipient_summary')->nullable()->after('body');
            $table->timestamp('last_activity_at')->nullable()->after('sent_at')->index();

            $table->index(['hopital_id', 'message_type', 'status', 'last_activity_at'], 'clinical_messages_internal_index');
        });

        Schema::table('clinical_message_recipients', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('recipient_id');
            $table->string('routing_key')->nullable()->after('display_name')->index();
            $table->timestamp('archived_at')->nullable()->after('acknowledged_at');
            $table->timestamp('deleted_at')->nullable()->after('archived_at');
            $table->timestamp('starred_at')->nullable()->after('deleted_at');
            $table->timestamp('important_at')->nullable()->after('starred_at');
        });

        Schema::create('message_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hopital_id')->constrained('hopitals')->cascadeOnDelete();
            $table->foreignId('dossier_patient_id')->nullable()->constrained('dossier_patients')->nullOnDelete();
            $table->string('subject');
            $table->string('category')->default('coordination');
            $table->string('priority')->default('normal');
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamps();

            $table->index(['hopital_id', 'last_message_at']);
        });

        Schema::create('message_user_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_message_id')->constrained('clinical_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('starred_at')->nullable();
            $table->timestamp('important_at')->nullable();
            $table->timestamps();

            $table->unique(['clinical_message_id', 'user_id'], 'message_user_status_unique');
            $table->index(['user_id', 'read_at']);
            $table->index(['user_id', 'archived_at', 'deleted_at']);
        });

        Schema::create('message_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_message_id')->constrained('clinical_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->index(['user_id', 'name']);
        });

        Schema::create('message_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_message_id')->constrained('clinical_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['clinical_message_id', 'user_id'], 'message_mentions_unique');
        });

        Schema::create('clinical_message_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinical_message_id')->nullable()->constrained('clinical_messages')->nullOnDelete();
            $table->foreignId('message_thread_id')->nullable()->constrained('message_threads')->nullOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action')->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical_message_audits');
        Schema::dropIfExists('message_mentions');
        Schema::dropIfExists('message_labels');
        Schema::dropIfExists('message_user_statuses');
        Schema::dropIfExists('message_threads');

        Schema::table('clinical_message_recipients', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'routing_key',
                'archived_at',
                'deleted_at',
                'starred_at',
                'important_at',
            ]);
        });

        Schema::table('clinical_messages', function (Blueprint $table) {
            $table->dropIndex('clinical_messages_internal_index');
            $table->dropColumn([
                'thread_id',
                'message_type',
                'recipient_summary',
                'last_activity_at',
            ]);
        });
    }
};
