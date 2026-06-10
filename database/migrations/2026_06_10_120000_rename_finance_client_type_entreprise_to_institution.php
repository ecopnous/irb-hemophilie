<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        DB::table('finance_clients')->where('type', 'entreprise')->update(['type' => 'institution']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE finance_clients MODIFY type ENUM('particulier', 'institution') NOT NULL DEFAULT 'particulier'");
        }
    }

    public function down(): void
    {
        DB::table('finance_clients')->where('type', 'institution')->update(['type' => 'entreprise']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE finance_clients MODIFY type ENUM('particulier', 'entreprise') NOT NULL DEFAULT 'particulier'");
        }
    }
};
