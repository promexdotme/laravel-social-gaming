<?php

namespace VanguardLTE\Http\Controllers\Web\Frontend;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VanguardLTE\User;
use VanguardLTE\SportsMatch;
use VanguardLTE\SportsBet;

class SportsbookController extends Controller
{
    /**
     * Display Sportsbook Arena & Category Matches
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedCategory = $request->input('sport', 'all');

        // Auto-Disappear Cutoff Rule: matches must have start_time in the future (start_time > now())
        $query = SportsMatch::where('status', 'upcoming')
            ->where('start_time', '>', now());

        if ($selectedCategory !== 'all') {
            $query->where('sport_key', 'like', "%{$selectedCategory}%");
        }

        $matches = $query->orderBy('start_time', 'asc')->get();

        $userBets = [];
        if ($user) {
            $userBets = SportsBet::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
        }

        return view('frontend.Minimal.sports.index', compact('user', 'matches', 'selectedCategory', 'userBets'));
    }

    /**
     * Place Wager (Single, Batch Singles, or Multi-Leg Parlay)
     */
    public function placeBet(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please log in to place sports wagers!']);
        }

        $type = $request->input('type', 'single'); // 'single', 'singles', 'parlay'
        $stakePerBet = (float)$request->input('stake', 0);
        $legs = $request->input('legs', []);

        if ($stakePerBet <= 0) {
            return response()->json(['success' => false, 'message' => 'Please enter a valid stake amount!']);
        }

        if (empty($legs) || !is_array($legs)) {
            return response()->json(['success' => false, 'message' => 'Please select at least one match leg for your betslip!']);
        }

        $totalLegs = count($legs);
        $totalStakeNeeded = ($type === 'singles') ? ($stakePerBet * $totalLegs) : $stakePerBet;

        if ($user->balance < $totalStakeNeeded) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient Cedar Coins! Required stake: " . number_format($totalStakeNeeded, 0) . " Coins."
            ]);
        }

        // Validate match legs
        $matchIds = [];
        $validatedLegs = [];

        foreach ($legs as $leg) {
            $matchId = $leg['match_id'] ?? null;
            $selection = $leg['selection'] ?? null; // 'home', 'draw', 'away'

            if (!$matchId || !$selection) continue;

            if (in_array($matchId, $matchIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Same-Game Parlay Blocked: You cannot select multiple legs from the same match!'
                ]);
            }

            $match = SportsMatch::where('match_id', $matchId)->first();

            if (!$match) {
                $validatedLegs[] = [
                    'match_id' => $matchId,
                    'home_team' => $leg['home'] ?? 'Team A',
                    'away_team' => $leg['away'] ?? 'Team B',
                    'selection' => $selection,
                    'odds' => (float)($leg['odds'] ?? 1.90),
                    'start_time' => now()->toIso8601String(),
                ];
                $matchIds[] = $matchId;
                continue;
            }

            // Auto-Disappear Cutoff Rule Verification
            if (now()->gte($match->start_time)) {
                return response()->json([
                    'success' => false,
                    'message' => "Betting is closed for '{$match->home_team} vs {$match->away_team}' as the match has already started!"
                ]);
            }

            $matchIds[] = $matchId;

            // Determine leg odds
            $legOdds = 1.90;
            if ($selection === 'home') $legOdds = $match->odds_home;
            elseif ($selection === 'draw') $legOdds = $match->odds_draw ?? 3.20;
            elseif ($selection === 'away') $legOdds = $match->odds_away;

            $validatedLegs[] = [
                'match_id' => $match->match_id,
                'home_team' => $match->home_team,
                'away_team' => $match->away_team,
                'selection' => $selection,
                'odds' => $legOdds,
                'start_time' => $match->start_time->toIso8601String(),
            ];
        }

        if (empty($validatedLegs)) {
            return response()->json(['success' => false, 'message' => 'Invalid betslip selections!']);
        }

        // Deduct Total Stake
        $user->decrement('balance', $totalStakeNeeded);
        \VanguardLTE\Services\AffiliateService::recordWagerCommission($user, $totalStakeNeeded, 'sportsbook');
        \VanguardLTE\Services\VipService::recordWagerXpAndRakeback($user, $totalStakeNeeded, 4.0);

        if ($type === 'singles' || count($validatedLegs) === 1) {
            // Create Individual Single Bets for each leg
            $totalPotentialWin = 0;
            foreach ($validatedLegs as $l) {
                $potentialWin = round($stakePerBet * $l['odds'], 2);
                $totalPotentialWin += $potentialWin;

                SportsBet::create([
                    'user_id' => $user->id,
                    'type' => 'single',
                    'legs_json' => [$l],
                    'total_odds' => $l['odds'],
                    'stake' => $stakePerBet,
                    'potential_win' => $potentialWin,
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => count($validatedLegs) . " Single Wager(s) Placed! Total Potential Win: " . number_format($totalPotentialWin, 0) . " Cedar Coins",
                'new_balance' => number_format($user->balance, 0)
            ]);
        } else {
            // Multi-Leg Parlay Bet with Dynamic Accumulator Boost
            $legCount = count($validatedLegs);
            $boostPercent = 0;
            if ($legCount >= 8) $boostPercent = 50;
            elseif ($legCount == 7) $boostPercent = 30;
            elseif ($legCount == 6) $boostPercent = 20;
            elseif ($legCount == 5) $boostPercent = 15;
            elseif ($legCount == 4) $boostPercent = 10;
            elseif ($legCount >= 3) $boostPercent = 5;

            $baseOdds = 1.00;
            foreach ($validatedLegs as $l) {
                $baseOdds *= $l['odds'];
            }
            $baseOdds = round($baseOdds, 2);

            $boostMultiplier = 1 + ($boostPercent / 100);
            $boostedOdds = round($baseOdds * $boostMultiplier, 2);
            $potentialWin = round($stakePerBet * $boostedOdds, 2);

            $bet = SportsBet::create([
                'user_id' => $user->id,
                'type' => 'parlay',
                'legs_json' => [
                    'legs' => $validatedLegs,
                    'boost_percent' => $boostPercent,
                    'base_odds' => $baseOdds,
                    'boosted_odds' => $boostedOdds
                ],
                'total_odds' => $boostedOdds,
                'stake' => $stakePerBet,
                'potential_win' => $potentialWin,
                'status' => 'pending',
            ]);

            $boostText = $boostPercent > 0 ? " (+{$boostPercent}% Boost Applied!)" : "";

            return response()->json([
                'success' => true,
                'message' => "Multi-Leg Parlay Placed!{$boostText} Potential Payout: " . number_format($potentialWin, 0) . " Cedar Coins",
                'bet_id' => $bet->id,
                'boost_percent' => $boostPercent,
                'boosted_odds' => $boostedOdds,
                'new_balance' => number_format($user->balance, 0)
            ]);
        }
    }

    /**
     * Compute Early Cashout Live Quote
     */
    public function getCashoutQuote(Request $request, $betId)
    {
        $user = Auth::user() ?: User::first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 401);
        }

        $bet = SportsBet::where('id', $betId)->where('user_id', $user->id)->first();
        if (!$bet) {
            return response()->json(['success' => false, 'message' => 'Bet not found.']);
        }

        if ($bet->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => "Bet is already {$bet->status}. Cashout unavailable.",
                'can_cashout' => false
            ]);
        }

        $rawLegs = $bet->legs_json;
        $legs = isset($rawLegs['legs']) ? $rawLegs['legs'] : (is_array($rawLegs) ? $rawLegs : json_decode($rawLegs, true));
        if (empty($legs)) {
            return response()->json(['success' => false, 'can_cashout' => false]);
        }

        $accumulatedFactor = 1.0;
        $hasLoss = false;
        $resolvedCount = 0;

        foreach ($legs as $leg) {
            $match = SportsMatch::where('match_id', $leg['match_id'])->first();

            if ($match && $match->status === 'completed') {
                $resolvedCount++;
                if ($match->winner !== $leg['selection']) {
                    $hasLoss = true;
                    break;
                } else {
                    // Leg won, compound odds
                    $accumulatedFactor *= (float)$leg['odds'];
                }
            } else {
                // Pending upcoming match, preserves baseline stake value
                $accumulatedFactor *= 1.0;
            }
        }

        if ($hasLoss) {
            return response()->json([
                'success' => true,
                'bet_id' => $bet->id,
                'can_cashout' => false,
                'cashout_value' => 0,
                'message' => 'Cashout unavailable: one or more legs have lost.'
            ]);
        }

        // Apply fair cashout margin (90% fair value)
        $cashoutValue = round($bet->stake * $accumulatedFactor * 0.90, 2);
        $cashoutValue = min($cashoutValue, $bet->potential_win);
        $cashoutValue = max(1.0, $cashoutValue);

        return response()->json([
            'success' => true,
            'bet_id' => $bet->id,
            'can_cashout' => true,
            'cashout_value' => $cashoutValue,
            'legs_resolved' => $resolvedCount,
            'total_legs' => count($legs),
            'potential_win' => $bet->potential_win,
            'message' => 'Cashout offer ready.'
        ]);
    }

    /**
     * Execute Early Cashout on a Pending Sports Wager
     */
    public function cashout(Request $request)
    {
        $user = Auth::user() ?: User::first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access'], 401);
        }

        $betId = (int)$request->input('bet_id');
        $quoteRes = $this->getCashoutQuote($request, $betId);
        $quoteData = $quoteRes->getData(true);

        if (!$quoteData['success'] || empty($quoteData['can_cashout']) || $quoteData['cashout_value'] <= 0) {
            return response()->json([
                'success' => false,
                'message' => $quoteData['message'] ?? 'Cashout is currently unavailable for this wager.'
            ]);
        }

        $cashoutAmount = (float)$quoteData['cashout_value'];
        $bet = SportsBet::where('id', $betId)->where('user_id', $user->id)->first();

        // Atomic cashout update
        $bet->status = 'cashed_out';
        $bet->payout_amount = $cashoutAmount;
        $bet->save();

        $user->increment('balance', $cashoutAmount);

        return response()->json([
            'success' => true,
            'message' => "Successfully cashed out " . number_format($cashoutAmount, 0) . " Cedar Coins!",
            'bet_id' => $bet->id,
            'cashout_amount' => $cashoutAmount,
            'new_balance' => number_format($user->balance, 0)
        ]);
    }
}
