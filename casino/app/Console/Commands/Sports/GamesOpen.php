<?php

namespace VanguardLTE\Console\Commands\Sports;

use VanguardLTE\Sports\Game;

class GamesOpen extends BaseSportsCommand
{
    protected $signature = 'sports:games:open';
    protected $description = 'Automatically open games for betting when start time is reached';
    protected $jobAlias = 'games_open';

    protected function handleCommand()
    {
        $games = Game::where('bet_start_time', '<=', now())
            ->where('start_time', '>', now())
            ->where('status', 0)
            ->get();

        $this->info("Found " . $games->count() . " games to open for betting.");

        foreach ($games as $game) {
            $game->status = 1;
            $game->save();
            $this->info("Opened game ID #{$game->id}: {$game->title}");
        }
    }
}
