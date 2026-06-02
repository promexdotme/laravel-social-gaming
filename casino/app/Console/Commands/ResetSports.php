<?php

namespace VanguardLTE\Console\Commands\Sports;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetSports extends Command
{
    protected $signature = 'sports:reset {--force}';
    protected $description = 'Truncate all sports tables and reseed default parameters';

    public function handle()
    {
        if (!$this->option('force') && !$this->confirm('This will wipe all sports data. Are you sure you want to proceed?')) {
            $this->info('Cancelled.');
            return 0;
        }

        $this->info('Wiping sports tables...');

        Schema::disableForeignKeyConstraints();

        DB::table('sports_cron_logs')->truncate();
        DB::table('sports_bet_items')->truncate();
        DB::table('sports_bets')->truncate();
        DB::table('sports_outcomes')->truncate();
        DB::table('sports_markets')->truncate();
        DB::table('sports_game_team')->truncate();
        DB::table('sports_games')->truncate();
        DB::table('sports_teams')->truncate();
        DB::table('sports_leagues')->truncate();
        DB::table('sports_categories')->truncate();

        Schema::enableForeignKeyConstraints();

        $this->info('Reseeding sportsbook default parameters...');
        
        $seeder = new \DatabaseSeeder();
        $seeder->run();

        $this->info('Sportsbook module successfully reset and reseeded!');
        return 0;
    }
}
