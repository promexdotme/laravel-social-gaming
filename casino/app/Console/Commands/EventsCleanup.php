<?php

namespace VanguardLTE\Console\Commands\Sports;

use VanguardLTE\Sports\Game;
use VanguardLTE\Sports\Market;
use VanguardLTE\Sports\Outcome;
use Carbon\Carbon;

class EventsCleanup extends BaseSportsCommand
{
    protected $signature = 'sports:events:cleanup';
    protected $description = 'Cleanup stale sports events, lock stale markets, and purge unused outcomes';
    protected $jobAlias = 'events_cleanup';

    protected function handleCommand()
    {
        $graceHours = (int)settings('sports_cleanup_grace_hours', 2);
        $threshold = now()->subHours($graceHours);

        $staleGames = Game::where('start_time', '<=', $threshold)
            ->whereNotIn('status', [2, 4])
            ->get();

        $this->info("Found " . $staleGames->count() . " stale games to mark ended.");
        foreach ($staleGames as $game) {
            $game->status = 4;
            $game->save();
            $this->info("Marked game ID #{$game->id} as Ended.");
        }

        $staleMarkets = Market::where('locked', 0)
            ->where('result_declared', 0)
            ->whereHas('game', function ($g) {
                $g->where('start_time', '<=', now());
            })
            ->get();

        $this->info("Found " . $staleMarkets->count() . " active markets on running/finished games to lock.");
        foreach ($staleMarkets as $market) {
            $market->locked = 1;
            $market->save();
            $this->info("Locked market ID #{$market->id} on game '{$market->game->title}'.");
        }

        $oldThreshold = now()->subDay();
        
        $unlinkedGames = Game::where('start_time', '<', $oldThreshold)
            ->whereDoesntHave('markets.betItems')
            ->get();

        $this->info("Found " . $unlinkedGames->count() . " old unlinked games to purge.");
        foreach ($unlinkedGames as $game) {
            foreach ($game->markets as $market) {
                Outcome::where('market_id', $market->id)->delete();
                $market->delete();
            }
            $game->teams()->detach();
            $game->delete();
            $this->info("Purged unlinked game ID #{$game->id}: {$game->title}");
        }
    }
}
