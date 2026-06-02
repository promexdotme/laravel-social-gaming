<?php

namespace VanguardLTE\Console\Commands\Sports;

use VanguardLTE\Sports\Services\SportsOddsSyncService;

class SyncOdds extends BaseSportsCommand
{
    protected $signature = 'sports:sync:odds';
    protected $description = 'Sync active odds from Odds API';
    protected $jobAlias = 'sync_odds';

    protected $syncService;

    public function __construct(SportsOddsSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    protected function handleCommand()
    {
        $this->syncService->syncOdds('active');
    }
}
