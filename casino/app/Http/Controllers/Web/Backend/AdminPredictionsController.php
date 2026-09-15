<?php

namespace VanguardLTE\Http\Controllers\Web\Backend;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VanguardLTE\User;
use VanguardLTE\PredictionMarket;
use VanguardLTE\PredictionVote;
use Illuminate\Support\Facades\Log;

class AdminPredictionsController extends Controller
{
    /**
     * Display Admin Prediction Control Desk
     */
    public function index()
    {
        $user = Auth::user();

        // Admin Security Enforcer
        if (!$user || (!$user->hasRole('admin') && !$user->is_admin && $user->role_id != 6)) {
            return redirect()->route('frontend.game.list')->with('error', 'Access Denied: Admin privileges required.');
        }

        $markets = PredictionMarket::with('creator')
            ->orderBy('id', 'desc')
            ->paginate(20);

        return view('backend.predictions.index', compact('user', 'markets'));
    }

    /**
     * Toggle Mute / Unmute Market
     */
    public function toggleMute($id)
    {
        $market = PredictionMarket::findOrFail($id);
        $market->status = ($market->status === 'muted') ? 'active' : 'muted';
        $market->save();

        return response()->json([
            'success' => true,
            'message' => "Market '{$market->title}' status updated to '{$market->status}'!",
            'new_status' => $market->status
        ]);
    }

    /**
     * Cancel Event & Refund All Player Wagers
     */
    public function cancelAndRefund($id)
    {
        $market = PredictionMarket::findOrFail($id);
        $market->status = 'cancelled';
        $market->save();

        $pendingVotes = PredictionVote::where('market_id', $market->market_id)
            ->where('status', 'pending')
            ->get();

        $refundedCount = 0;
        $totalRefundedCoins = 0;

        foreach ($pendingVotes as $vote) {
            $vote->status = 'refunded';
            $vote->payout_amount = $vote->stake;
            $vote->save();

            $user = User::find($vote->user_id);
            if ($user) {
                $user->increment('balance', $vote->stake);
                $refundedCount++;
                $totalRefundedCoins += $vote->stake;
            }
        }

        Log::info("[Admin Prediction Cancel] Market ID '{$market->market_id}' cancelled. Refunded {$refundedCount} votes total {$totalRefundedCoins} Coins.");

        return response()->json([
            'success' => true,
            'message' => "Event Cancelled! Refunded {$refundedCount} player wager(s) totaling " . number_format($totalRefundedCoins, 0) . " Cedar Coins."
        ]);
    }

    /**
     * Inject Bot Liquidity into Pool (Shift Odds Ratio)
     */
    public function injectPool(Request $request, $id)
    {
        $market = PredictionMarket::findOrFail($id);
        $side = strtolower($request->input('side', 'yes')); // 'yes', 'no'
        $amount = (float)$request->input('amount', 5000);

        if ($amount <= 0) {
            return response()->json(['success' => false, 'message' => 'Please enter a valid injection amount!']);
        }

        if ($side === 'yes') {
            $market->increment('pool_yes', $amount);
        } else {
            $market->increment('pool_no', $amount);
        }

        return response()->json([
            'success' => true,
            'message' => "Injected " . number_format($amount, 0) . " Cedar Coins into {$side->strtoupper()} Pool!",
            'new_yes_odds' => number_format($market->yes_odds, 2),
            'new_no_odds' => number_format($market->no_odds, 2)
        ]);
    }

    /**
     * Settle Market Outcome (YES or NO)
     */
    public function settle(Request $request, $id)
    {
        $market = PredictionMarket::findOrFail($id);
        $resolution = strtolower($request->input('resolution', 'yes')); // 'yes', 'no'

        if (!in_array($resolution, ['yes', 'no'])) {
            return response()->json(['success' => false, 'message' => 'Invalid resolution choice!']);
        }

        $market->status = 'resolved';
        $market->resolution = $resolution;
        $market->save();

        $pendingVotes = PredictionVote::where('market_id', $market->market_id)
            ->where('status', 'pending')
            ->get();

        $winnersCount = 0;
        $totalPaid = 0.00;

        foreach ($pendingVotes as $vote) {
            if ($vote->choice === $resolution) {
                $vote->status = 'won';
                $vote->payout_amount = $vote->potential_win;
                $winnersCount++;
                $totalPaid += $vote->potential_win;

                $user = User::find($vote->user_id);
                if ($user) {
                    $user->increment('balance', $vote->potential_win);
                }
            } else {
                $vote->status = 'lost';
            }
            $vote->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Market Settled as " . strtoupper($resolution) . "! Winners: {$winnersCount} | Total Paid: " . number_format($totalPaid, 0) . " Cedar Coins."
        ]);
    }
}
