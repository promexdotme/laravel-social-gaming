<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Sports\Bet;
use VanguardLTE\Sports\BetItem;
use VanguardLTE\Sports\Outcome;
use VanguardLTE\Sports\Market;
use VanguardLTE\Sports\Game;
use VanguardLTE\Sports\Services\WalletLedgerService;

class SportsSettlementController extends Controller
{
    protected $ledger;

    public function __construct(WalletLedgerService $ledger)
    {
        $this->ledger = $ledger;
    }

    public function index(Request $request)
    {
        $games = Game::whereHas('markets', function ($m) {
            $m->where('result_declared', 0);
        })
        ->with(['markets' => function ($m) {
            $m->where('result_declared', 0)->with('outcomes');
        }, 'league.category'])
        ->orderBy('start_time', 'desc')
        ->paginate(15);

        return view('liteback.sports.settlements', compact('games'));
    }

    public function settle(Outcome $outcome, Request $request)
    {
        $market = $outcome->market;
        if ($market->result_declared) {
            return redirect()->back()->withErrors('This market is already settled.');
        }

        DB::transaction(function () use ($outcome, $market) {
            $outcome->winner = 1;
            $outcome->save();

            Outcome::where('market_id', $market->id)
                ->where('id', '!=', $outcome->id)
                ->update(['winner' => 0]);

            $market->result_declared = 1;
            $market->win_outcome_id = $outcome->id;
            $market->save();

            BetItem::where('market_id', $market->id)
                ->where('outcome_id', $outcome->id)
                ->update(['status' => 1]);

            BetItem::where('market_id', $market->id)
                ->where('outcome_id', '!=', $outcome->id)
                ->update(['status' => 3]);

            $pendingBets = Bet::where('is_settled', 0)
                ->whereHas('items', function ($q) use ($market) {
                    $q->where('market_id', $market->id);
                })
                ->with('items')
                ->get();

            foreach ($pendingBets as $bet) {
                $allItems = $bet->items;
                
                $hasLost = $allItems->contains('status', 3);
                $hasPending = $allItems->contains('status', 2);

                if ($hasLost) {
                    $bet->status = 3;
                    $bet->is_settled = 1;
                    $bet->result_time = now();
                    $bet->save();
                } elseif (!$hasPending) {
                    $bet->status = 1;
                    $bet->is_settled = 1;
                    $bet->result_time = now();

                    $multiplier = 1.0;
                    foreach ($allItems as $item) {
                        if ($item->status === 1) {
                            $multiplier *= (float)$item->odds;
                        }
                    }
                    $finalReturn = $bet->stake_amount * $multiplier;
                    $bet->return_amount = $finalReturn;
                    $bet->save();

                    $user = $bet->user;
                    $this->ledger->credit($user, $finalReturn, "Sports Bet Win Payout", 'sports_bet_settle', $bet->id);
                }
            }
        });

        return redirect()->back()->with('success', 'Market settled successfully and payouts processed.');
    }

    public function refundBet(Bet $bet)
    {
        if ($bet->is_settled) {
            return redirect()->back()->withErrors('This bet is already settled.');
        }

        DB::transaction(function () use ($bet) {
            $bet->status = 4;
            $bet->is_settled = 1;
            $bet->result_time = now();
            $bet->save();

            BetItem::where('bet_id', $bet->id)->update(['status' => 4]);

            $user = $bet->user;
            $this->ledger->credit($user, (float)$bet->stake_amount, "Sports Bet Refund", 'sports_refund', $bet->id);
        });

        return redirect()->back()->with('success', 'Bet refunded successfully.');
    }
}
