<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = DB::getTablePrefix();
        $source = $prefix . 'games';
        $target = $prefix . 'games_inactive';

        // Only attempt if the source table exists.
        $tables = DB::select("SHOW TABLES LIKE '{$source}'");
        if (empty($tables)) {
            return;
        }

        if (!Schema::hasTable('games_inactive')) {
            DB::statement("CREATE TABLE {$target} LIKE {$source}");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('games_inactive');
    }
};
