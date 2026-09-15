<?php

use Illuminate\Database\Seeder;
use VanguardLTE\LottoGame;

class LottoGameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        LottoGame::updateOrCreate(
            ['slug' => 'daily-lucky-4'],
            [
                'title' => 'Daily Lucky 4 (4 out of 20)',
                'pick_count' => 4,
                'max_number' => 20,
                'entry_fee' => 500.00,
                'jackpot_pool' => 250000.00,
                'draw_interval' => 'daily',
                'draw_time' => '23:55',
                'is_active' => true,
            ]
        );

        LottoGame::updateOrCreate(
            ['slug' => 'grand-cedar-6'],
            [
                'title' => 'Grand Cedar 6 (6 out of 49)',
                'pick_count' => 6,
                'max_number' => 49,
                'entry_fee' => 1000.00,
                'jackpot_pool' => 1000000.00,
                'draw_interval' => 'daily',
                'draw_time' => '00:00',
                'is_active' => true,
            ]
        );

        LottoGame::updateOrCreate(
            ['slug' => 'hourly-mini-3'],
            [
                'title' => 'Hourly Mini (3 out of 10)',
                'pick_count' => 3,
                'max_number' => 10,
                'entry_fee' => 100.00,
                'jackpot_pool' => 25000.00,
                'draw_interval' => 'hourly',
                'draw_time' => '00',
                'is_active' => true,
            ]
        );
    }
}
