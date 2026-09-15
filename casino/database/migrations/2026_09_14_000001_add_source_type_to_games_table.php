<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('games', 'source_type')) {
            Schema::table('games', function (Blueprint $table) {
                $table->string('source_type', 32)->default('default')->after('view');
                $table->text('custom_path')->nullable()->after('source_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('games', 'source_type')) {
            Schema::table('games', function (Blueprint $table) {
                $table->dropColumn(['source_type', 'custom_path']);
            });
        }
    }
};