<?php

namespace VanguardLTE\Console\Commands\Sports;

use VanguardLTE\Sports\Services\SportsOddsSyncService;

class SyncUpcoming extends BaseSportsCommand
{
    protected $signature = 'sports:sync:upcoming';
    protected $description = 'Sync upcoming pre-match odds from Odds API';
    protected $jobAlias = 'sync_upcoming';

    protected $syncService;

    public function __construct(SportsOddsSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    protected function handleCommand()
    {
        $this->syncService->syncUpcomingOdds();
    }
}
