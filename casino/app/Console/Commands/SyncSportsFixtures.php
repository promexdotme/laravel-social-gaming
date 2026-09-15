<?php

namespace VanguardLTE\Console\Commands;

use Illuminate\Console\Command;
use VanguardLTE\Services\OddsApiService;

class SyncSportsFixtures extends Command
{
    protected $signature = 'sports:sync-fixtures';
    protected $description = 'Sync upcoming pre-match fixtures from The Odds API';

    public function handle(OddsApiService $oddsApi)
    {
        $this->info('Fetching pre-match sports fixtures from The Odds API...');
        $result = $oddsApi->syncUpcomingFixtures();
        $this->info("Sportsbook Sync Complete! Fixtures updated: {$result['synced_count']}");
        return 0;
    }
}
