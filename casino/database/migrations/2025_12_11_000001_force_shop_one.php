<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $tablesWithShop = [
            'w_users',
            'w_games',
            'w_fish_bank',
            'w_game_bank',
            'w_jpg',
            'w_stat_game',
            'w_statistics',
            'w_statistics_add',
            'w_subsessions',
            'w_progress_users',
            'w_open_shift',
            'w_open_shift_temp',
            'w_rewards',
            'w_shop_categories',
            'w_shops_user',
            'w_sms_bonus_items',
            'w_sms_mailings',
            'w_sms_mailing_messages',
            'w_sms',
            'w_securities',
            'w_credits',
            'w_info_shop',
            'w_tournament_stats',
            'w_tournament_bots',
            'w_tournament_games',
            'w_tournament_prizes',
            'w_tournament_categories',
            'w_welcomebonuses',
            'w_wheelfortune',
            'w_withdraw_funds',
        ];

        foreach ($tablesWithShop as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'shop_id')) {
                DB::statement("UPDATE `{$table}` SET `shop_id` = 1");
            }
        }

        // Collapse shops to a single record with id=1 if table exists.
        if (Schema::hasTable('w_shops')) {
            DB::statement("UPDATE `w_shops` SET `id` = 1, `parent_id` = 0");
            DB::statement("DELETE FROM `w_shops` WHERE `id` != 1");
        }
    }

    public function down()
    {
        // No-op: irreversible consolidation.
    }
};
