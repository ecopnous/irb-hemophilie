<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $images = DB::table('images')
            ->whereNull('laboratoire_id')
            ->where('path', 'like', 'laboratoire/%')
            ->get(['id', 'path']);

        foreach ($images as $image) {
            if (! preg_match('#^laboratoire/(\d+)/#', (string) $image->path, $matches)) {
                continue;
            }

            DB::table('images')
                ->where('id', $image->id)
                ->update(['laboratoire_id' => (int) $matches[1]]);
        }
    }

    public function down(): void
    {
        //
    }
};
