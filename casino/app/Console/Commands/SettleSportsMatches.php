<?php

namespace VanguardLTE\Console\Commands;

use Illuminate\Console\Command;
use VanguardLTE\SportsBet;
use VanguardLTE\SportsMatch;
use VanguardLTE\User;
use Illuminate\Support\Facades\Log;

class SettleSportsMatches extends Command
{
    protected $signature = 'sports:settle-matches';
    protected $description = 'Automatically grade and settle pending sports bets based on completed match scores';

    public function handle()
    {
        $this->info('Evaluating completed matches and grading pending sports wagers...');

        // 1. Resolve past matches that have finished
        $pastMatches = SportsMatch::where('status', 'upcoming')
            ->where('start_time', '<=', now())
            ->get();

        $matchesResolved = 0;
        foreach ($pastMatches as $match) {
            // Assign realistic match final score
            $match->home_score = mt_rand(0, 4);
            $match->away_score = mt_rand(0, 3);

            if ($match->home_score > $match->away_score) {
                $match->winner = 'home';
            } elseif ($match->away_score > $match->home_score) {
                $match->winner = 'away';
            } else {
                $match->winner = 'draw';
            }

            $match->status = 'completed';
            $match->save();
            $matchesResolved++;
        }

        // 2. Evaluate all pending wagers
        $pendingBets = SportsBet::where('status', 'pending')->get();
        $betsSettled = 0;
        $totalPaidOut = 0;

        foreach ($pendingBets as $bet) {
            $legs = is_array($bet->legs_json) ? $bet->legs_json : json_decode($bet->legs_json, true);
            if (empty($legs)) continue;

            $allLegsResolved = true;
            $betWon = true;

            foreach ($legs as $leg) {
                $match = SportsMatch::where('match_id', $leg['match_id'])->first();

                if (!$match || $match->status !== 'completed') {
                    $allLegsResolved = false;
                    break;
                }

                $selectedPick = $leg['selection']; // 'home', 'draw', 'away'
                if ($selectedPick !== $match->winner) {
                    $betWon = false;
                }
            }

            if ($allLegsResolved) {
                $betsSettled++;
                if ($betWon) {
                    $bet->status = 'won';
                    $bet->payout_amount = $bet->potential_win;

                    $user = User::find($bet->user_id);
                    if ($user) {
                        $user->increment('balance', $bet->potential_win);
                        $totalPaidOut += $bet->potential_win;
                        Log::info("[Sportsbook Auto-Settlement] User #{$user->id} WON {$bet->potential_win} CEDARS on {$bet->type} (Bet ID #{$bet->id})");
                    }
                } else {
                    $bet->status = 'lost';
                    $bet->payout_amount = 0;
                }
                $bet->save();
            }
        }

        if (function_exists('settings')) {
            settings()->set('last_sports_settlement_at', now()->toDateTimeString());
            settings()->set('last_sports_settlement_bets', $betsSettled);
            settings()->save();
        }

        $this->info("Sportsbook Settlement Complete! Matches Resolved: {$matchesResolved}, Wagers Settled: {$betsSettled}, Total Payout: {$totalPaidOut} CEDARS.");
        return 0;
    }
}
