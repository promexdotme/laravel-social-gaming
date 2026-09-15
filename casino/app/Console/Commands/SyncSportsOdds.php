<?php

namespace VanguardLTE\Console\Commands;

use Illuminate\Console\Command;
use VanguardLTE\Services\OddsApiService;

class SyncSportsOdds extends Command
{
    protected $signature = 'sports:sync-odds {--force : Force sync regardless of timing}';
    protected $description = 'Ingest real-time match fixtures and odds from The Odds API with failover simulation';

    public function handle(OddsApiService $oddsApi)
    {
        $this->info('Starting automated sports odds ingestion...');
        $result = $oddsApi->syncUpcomingFixtures();
        $this->info("Sportsbook Sync Complete! Fixtures updated: {$result['synced_count']} at {$result['synced_at']}");
        return 0;
    }
}
