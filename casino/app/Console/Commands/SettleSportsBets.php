<?php

namespace VanguardLTE\Console\Commands;

use Illuminate\Console\Command;
use VanguardLTE\SportsBet;
use VanguardLTE\SportsMatch;
use VanguardLTE\User;
use Illuminate\Support\Facades\Log;

class SettleSportsBets extends Command
{
    protected $signature = 'sports:settle-bets';
    protected $description = 'Settle pending single and parlay sports wagers based on match outcomes';

    public function handle()
    {
        $pendingBets = SportsBet::where('status', 'pending')->get();

        if ($pendingBets->isEmpty()) {
            $this->info('No pending sports bets found to settle.');
            return 0;
        }

        $settledCount = 0;

        foreach ($pendingBets as $bet) {
            $legs = is_array($bet->legs_json) ? $bet->legs_json : json_decode($bet->legs_json, true);
            if (empty($legs)) continue;

            $allLegsResolved = true;
            $parlayWon = true;

            foreach ($legs as $leg) {
                $match = SportsMatch::where('match_id', $leg['match_id'])->first();

                // If match not completed, assign simulated winner if past start time
                if ($match && $match->status !== 'completed' && now()->gte($match->start_time)) {
                    // Simulate score determination for past fixtures
                    $match->home_score = mt_rand(0, 3);
                    $match->away_score = mt_rand(0, 3);
                    
                    if ($match->home_score > $match->away_score) $match->winner = 'home';
                    elseif ($match->away_score > $match->home_score) $match->winner = 'away';
                    else $match->winner = 'draw';
                    
                    $match->status = 'completed';
                    $match->save();
                }

                if (!$match || $match->status !== 'completed') {
                    $allLegsResolved = false;
                    break;
                }

                // Check if leg selection matches winner
                $selectedPick = $leg['selection']; // 'home', 'draw', 'away'
                if ($selectedPick !== $match->winner) {
                    $parlayWon = false;
                }
            }

            if ($allLegsResolved) {
                $settledCount++;
                if ($parlayWon) {
                    $bet->status = 'won';
                    $bet->payout_amount = $bet->potential_win;

                    $user = User::find($bet->user_id);
                    if ($user) {
                        $user->increment('balance', $bet->potential_win);
                        Log::info("[Sportsbook Win] User ID {$user->id} WON {$bet->potential_win} Cedars on {$bet->type} bet (Bet ID #{$bet->id})");
                    }
                } else {
                    $bet->status = 'lost';
                }
                $bet->save();
            }
        }

        $this->info("Sportsbook Settlement Complete! Wagers Settled: {$settledCount}");
        return 0;
    }
}
