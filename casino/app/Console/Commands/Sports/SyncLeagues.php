<?php

namespace VanguardLTE\Console\Commands\Sports;

use VanguardLTE\Sports\Services\SportsOddsSyncService;

class SyncLeagues extends BaseSportsCommand
{
    protected $signature = 'sports:sync:leagues';
    protected $description = 'Sync leagues from Odds API';
    protected $jobAlias = 'sync_leagues';

    protected $syncService;

    public function __construct(SportsOddsSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    protected function handleCommand()
    {
        $this->syncService->syncSports();
    }
}
