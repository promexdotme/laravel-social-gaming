<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SportsbookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            'sports_feature_betting_enabled' => '1',
            'sports_feature_manual_games' => '1',
            'sports_feature_manual_odds_override' => '1',
            'sports_feature_exports_enabled' => '1',
            'sports_feature_admin_sync_enabled' => '1',
            'sports_feature_admin_settlement_enabled' => '1',
            'ods_api_key' => '',
            'ods_api_regions' => 'us',
            'ods_api_markets' => 'h2h',
            'single_bet_min_limit' => '1',
            'single_bet_max_limit' => '10000',
            'multi_bet_min_limit' => '1',
            'multi_bet_max_limit' => '10000',
        ];

        foreach ($settings as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value]
            );
        }

        $categories = [
            ['name' => 'Soccer', 'odds_api_name' => 'Soccer', 'regions' => '["eu","us","uk","au"]'],
            ['name' => 'Basketball', 'odds_api_name' => 'Basketball', 'regions' => '["us"]'],
            ['name' => 'American Football', 'odds_api_name' => 'American Football', 'regions' => '["us"]'],
            ['name' => 'Ice Hockey', 'odds_api_name' => 'Ice Hockey', 'regions' => '["us"]'],
            ['name' => 'Tennis', 'odds_api_name' => 'Tennis', 'regions' => '["us","eu"]'],
            ['name' => 'Cricket', 'odds_api_name' => 'Cricket', 'regions' => '["eu","uk"]'],
        ];

        foreach ($categories as $cat) {
            DB::table('sports_categories')->updateOrInsert(
                ['slug' => Str::slug($cat['name'])],
                [
                    'name' => $cat['name'],
                    'odds_api_name' => $cat['odds_api_name'],
                    'regions' => $cat['regions'],
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
