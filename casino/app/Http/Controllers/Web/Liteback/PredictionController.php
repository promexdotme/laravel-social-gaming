<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VanguardLTE\PredictionMarket;
use VanguardLTE\PredictionVote;
use VanguardLTE\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PredictionController extends Controller
{
    /**
     * Display Prediction Markets Control
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $query = PredictionMarket::withCount('votes')->orderBy('id', 'desc');

        if ($status !== 'all') {
            if ($status === 'active') {
                $query->where('status', 'active');
            } elseif ($status === 'settled') {
                $query->where('status', 'like', 'settled%');
            }
        }

        $markets = $query->paginate(20)->appends($request->only('status'));

        $totalActive = PredictionMarket::where('status', 'active')->count();
        $totalSettled = PredictionMarket::where('status', 'like', 'settled%')->count();
        $totalVolume = (float) PredictionMarket::sum(DB::raw('pool_yes + pool_no'));

        return view('liteback.predictions.index', compact('markets', 'totalActive', 'totalSettled', 'totalVolume', 'status'));
    }

    /**
     * Create New Prediction Market Topic with Initial Share Probability
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'yes_price' => 'required|numeric|min:0.01|max:0.99',
            'initial_liquidity' => 'nullable|numeric|min:500',
            'end_date' => 'nullable|date',
        ]);

        $yesPrice = (float) $request->input('yes_price', 0.50);
        $totalSeed = (float) $request->input('initial_liquidity', 2000.00);

        $poolYes = round($totalSeed * $yesPrice, 2);
        $poolNo = round($totalSeed * (1.00 - $yesPrice), 2);

        $marketId = 'pred_' . strtolower(uniqid());

        $endDate = $request->filled('end_date')
            ? Carbon::parse($request->input('end_date'))->setTimezone('UTC')
            : now()->addDays(30);

        PredictionMarket::create([
            'market_id' => $marketId,
            'creator_id' => auth()->id() ?? 1,
            'category' => strtolower($request->input('category')),
            'title' => $request->input('title'),
            'description' => $request->input('description'),
            'pool_yes' => $poolYes,
            'pool_no' => $poolNo,
            'end_date' => $endDate,
            'status' => 'active',
        ]);

        $noPrice = round(1.00 - $yesPrice, 2);
        $yesCents = round($yesPrice * 100);
        $noCents = round($noPrice * 100);
        return redirect()->route('liteback.predictions.index')->with('success', "Prediction Market created! Initial Share Price: YES {$yesPrice} ({$yesCents}¢) / NO {$noPrice} ({$noCents}¢).");
    }

    /**
     * Settle Prediction Market Topic Outcome and Pay Out Shares
     */
    public function settle(Request $request, $id)
    {
        $outcome = strtolower(trim($request->input('outcome'))); // 'yes', 'no', 'void'
        if (!in_array($outcome, ['yes', 'no', 'void'])) {
            return redirect()->back()->withErrors('Invalid resolution outcome. Must be YES, NO, or VOID.');
        }

        $market = PredictionMarket::find($id);
        if (!$market) {
            return redirect()->back()->withErrors('Market not found.');
        }

        if (str_starts_with($market->status, 'settled')) {
            return redirect()->back()->withErrors('Market has already been settled.');
        }

        $votes = PredictionVote::where('market_id', $market->market_id)
            ->where('status', 'pending')
            ->get();

        $winnersCount = 0;
        $totalPaidOut = 0.0;

        DB::transaction(function () use ($market, $votes, $outcome, &$winnersCount, &$totalPaidOut) {
            foreach ($votes as $vote) {
                if ($outcome === 'void') {
                    // Refund initial stake
                    $refund = (float) $vote->stake;
                    $vote->status = 'refunded';
                    $vote->payout_amount = $refund;
                    $vote->save();

                    User::where('id', $vote->user_id)->increment('balance', $refund);
                    $totalPaidOut += $refund;
                    $winnersCount++;
                } else {
                    if (strtolower($vote->choice) === $outcome) {
                        // Winner: pays potential_win (shares_count * 1.00)
                        $winAmount = (float) ($vote->potential_win ?: ($vote->shares_count ?: ($vote->stake * $vote->odds)));
                        $vote->status = 'won';
                        $vote->payout_amount = $winAmount;
                        $vote->save();

                        User::where('id', $vote->user_id)->increment('balance', $winAmount);
                        $totalPaidOut += $winAmount;
                        $winnersCount++;
                    } else {
                        // Lost
                        $vote->status = 'lost';
                        $vote->payout_amount = 0.00;
                        $vote->save();
                    }
                }
            }

            $market->status = 'settled_' . $outcome;
            $market->resolution = strtoupper($outcome);
            $market->save();
        });

        $msg = "Market #{$market->id} settled as " . strtoupper($outcome) . "! Credited " . number_format($totalPaidOut, 0) . " Cedar Coins to {$winnersCount} winner accounts.";
        return redirect()->route('liteback.predictions.index')->with('success', $msg);
    }
}
