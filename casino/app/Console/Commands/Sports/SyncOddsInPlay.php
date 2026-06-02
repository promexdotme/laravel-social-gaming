<?php

namespace VanguardLTE\Console\Commands\Sports;

use VanguardLTE\Sports\Services\SportsOddsSyncService;

class SyncOddsInPlay extends BaseSportsCommand
{
    protected $signature = 'sports:sync:odds-inplay';
    protected $description = 'Sync in-play live odds from Odds API';
    protected $jobAlias = 'sync_odds_inplay';

    protected $syncService;

    public function __construct(SportsOddsSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    protected function handleCommand()
    {
        $this->syncService->syncOdds('running');
    }
}
