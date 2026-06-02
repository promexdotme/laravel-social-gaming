<?php

namespace VanguardLTE\Console\Commands\Sports;

use VanguardLTE\Sports\Services\SportsOddsSyncService;

class SyncGames extends BaseSportsCommand
{
    protected $signature = 'sports:sync:games';
    protected $description = 'Sync games from Odds API';
    protected $jobAlias = 'sync_games';

    protected $syncService;

    public function __construct(SportsOddsSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    protected function handleCommand()
    {
        $this->syncService->syncGames();
    }
}
