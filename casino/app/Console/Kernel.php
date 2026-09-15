<?php 

namespace VanguardLTE\Console
{
    class Kernel extends \Illuminate\Foundation\Console\Kernel
    {
        protected $commands = [
            Commands\SettleCedarCrash::class,
            Commands\Sports\SyncLeagues::class,
            Commands\Sports\SyncGames::class,
            Commands\Sports\SyncOdds::class,
            Commands\Sports\SyncOddsInPlay::class,
            Commands\Sports\GamesOpen::class,
            Commands\Sports\EventsCleanup::class,
            Commands\Sports\ResetSports::class,
            Commands\Sports\SyncUpcoming::class,
            Commands\Sports\SyncAll::class,
            Commands\InjectMockWebSocket::class,
            Commands\DrawLotto::class,
            Commands\SyncSportsFixtures::class,
            Commands\SettleSportsBets::class,
            Commands\SyncSportsOdds::class,
            Commands\SettleSportsMatches::class,
        ];

        protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule)
        {
            $schedule->command('cedar:settle-crash')->everyMinute()->withoutOverlapping();
            $schedule->command('queue:work --daemon')->everyMinute()->withoutOverlapping();
            $schedule->call(function()
            {
                \Spatie\DbDumper\Databases\MySql::create()->setDbName(config('database.connections.mysql.database'))->setUserName(config('database.connections.mysql.username'))->setPassword(config('database.connections.mysql.password'))->dumpToFile(base_path() . '/backups/' . date('Hi_dmY') . '.sql');
            })->daily();
            $_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332 = 45;
            $schedule->call(new Schedules\SMSBonuses($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\Securities($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyFiveMinutes();
            $schedule->call(new Schedules\ShopCreates($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\ShopDeletes($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\Synchronization($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\QuickShops($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\HierarchyUsersCache($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyFiveMinutes();
            $schedule->call(new Schedules\TreeCache($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyFiveMinutes();
            $schedule->call(new Schedules\HotGamesCache($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyThreeHours();
            $schedule->call(new Schedules\BankDecrease($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyThreeHours();
            $schedule->call(new Schedules\Notifications($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\ClearLogs($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\GameEvents($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyFiveMinutes();
            $schedule->call(new Schedules\RemoveGamesWithoutFolder($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\SMSMailings($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();
            $schedule->call(new Schedules\EveryFiveMinutesCleanUp($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyFiveMinutes();
            $schedule->call(new Schedules\EveryMinuteCleanUp($_obf_0D2F242F2D052B0938193F2D0D2F192F27160616153332))->everyMinute();

            // Sportsbook scheduling tasks
            $schedule->command('sports:sync:odds-inplay')->everyMinute()->withoutOverlapping();
            $schedule->command('sports:games:open')->everyMinute();
            $schedule->command('sports:sync:odds')->everyFiveMinutes();
            $schedule->command('sports:events:cleanup')->hourly();
            $schedule->command('sports:sync:games')->hourly();
            $schedule->command('sports:sync:leagues')->daily();
            $schedule->command('sports:sync:upcoming')->daily();
            $schedule->command('sports:sync-odds')->everyThirtyMinutes();
            $schedule->command('sports:settle-matches')->everyFiveMinutes();

            // Automated Cedar Lotto Draw Runner (Daily at Midnight 00:00)
            $schedule->command('casino:draw-lotto')->dailyAt('00:00');
        }

        protected function commands()
        {
            require(base_path('routes/console.php'));
        }
    }
}
