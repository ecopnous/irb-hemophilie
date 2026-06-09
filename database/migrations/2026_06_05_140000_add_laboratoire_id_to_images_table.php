<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            Schema::create('images_new', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('path');
                $table->foreignId('laboratoire_id')->nullable()->constrained('laboratoires')->cascadeOnDelete();
                $table->foreignId('acte_consultation_id')->nullable()->constrained('acte_consultation')->cascadeOnDelete();
                $table->timestamps();
            });

            DB::statement(
                'INSERT INTO images_new (id, name, path, acte_consultation_id, created_at, updated_at)
                 SELECT id, name, path, acte_consultation_id, created_at, updated_at FROM images',
            );

            Schema::drop('images');
            Schema::rename('images_new', 'images');
            Schema::enableForeignKeyConstraints();

            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $table->foreignId('laboratoire_id')->nullable()->after('path')->constrained('laboratoires')->cascadeOnDelete();
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropForeign(['acte_consultation_id']);
        });

        DB::statement('ALTER TABLE images MODIFY acte_consultation_id BIGINT UNSIGNED NULL');

        Schema::table('images', function (Blueprint $table) {
            $table->foreign('acte_consultation_id')->references('id')->on('acte_consultation')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::disableForeignKeyConstraints();

            Schema::create('images_old', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('path');
                $table->foreignId('acte_consultation_id')->constrained('acte_consultation')->cascadeOnDelete();
                $table->timestamps();
            });

            DB::statement(
                'INSERT INTO images_old (id, name, path, acte_consultation_id, created_at, updated_at)
                 SELECT id, name, path, acte_consultation_id, created_at, updated_at
                 FROM images
                 WHERE acte_consultation_id IS NOT NULL',
            );

            Schema::drop('images');
            Schema::rename('images_old', 'images');
            Schema::enableForeignKeyConstraints();

            return;
        }

        Schema::table('images', function (Blueprint $table) {
            $table->dropForeign(['laboratoire_id']);
            $table->dropColumn('laboratoire_id');
        });

        Schema::table('images', function (Blueprint $table) {
            $table->dropForeign(['acte_consultation_id']);
        });

        DB::statement('ALTER TABLE images MODIFY acte_consultation_id BIGINT UNSIGNED NOT NULL');

        Schema::table('images', function (Blueprint $table) {
            $table->foreign('acte_consultation_id')->references('id')->on('acte_consultation')->cascadeOnDelete();
        });
    }
};
