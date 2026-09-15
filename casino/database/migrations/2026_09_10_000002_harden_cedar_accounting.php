<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $table = DB::connection()->getTablePrefix() . 'stat_game';
            // Wallet and its audit must share transactional storage. Retain all existing rows.
            DB::statement('ALTER TABLE `' . str_replace('`', '``', $table) . '` ENGINE=InnoDB');
        }
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('vip_rakeback_remainder', 10, 6)->default(0);
            $table->decimal('vip_xp_remainder', 8, 4)->default(0);
        });
    }
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['vip_rakeback_remainder', 'vip_xp_remainder']);
        });
        // Never downgrade an accounting table back to non-transactional MyISAM.
    }
};
