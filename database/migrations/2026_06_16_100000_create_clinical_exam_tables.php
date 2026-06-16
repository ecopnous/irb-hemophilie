<?php

use App\Models\Consultation;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinical_exam_field_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('section_key');
            $table->string('section_label');
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->string('field_type');
            $table->string('value_label')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['section_key', 'sort_order']);
        });

        Schema::create('consultation_clinical_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Consultation::class)->unique()->constrained()->cascadeOnDelete();
            $table->date('examined_at')->nullable();
            $table->text('synthesis')->nullable();
            $table->foreignIdFor(User::class, 'filled_by_user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('consultation_clinical_exam_values', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Consultation::class)->constrained()->cascadeOnDelete();
            $table->foreignId('clinical_exam_field_definition_id')
                ->constrained('clinical_exam_field_definitions')
                ->cascadeOnDelete();
            $table->boolean('present')->nullable();
            $table->string('value_text')->nullable();
            $table->decimal('value_number', 10, 2)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(
                ['consultation_id', 'clinical_exam_field_definition_id'],
                'consultation_clinical_exam_value_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultation_clinical_exam_values');
        Schema::dropIfExists('consultation_clinical_exams');
        Schema::dropIfExists('clinical_exam_field_definitions');
    }
};
