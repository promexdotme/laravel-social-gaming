<?php

namespace VanguardLTE\Http\Controllers\Web\Frontend;

use Illuminate\Http\Request;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Sports\Category;
use VanguardLTE\Sports\Game;
use VanguardLTE\Sports\Outcome;
use VanguardLTE\Sports\Bet;
use VanguardLTE\Sports\Services\SportsBetService;
use Illuminate\Support\Facades\Auth;

class SportsController extends Controller
{
    protected $betService;

    public function __construct(SportsBetService $betService)
    {
        $this->betService = $betService;
    }

    public function index(Request $request, $categorySlug = 'all')
    {
        $categories = Category::active()->get();
        $query = Game::openForBetting()
            ->hasActiveCategory()
            ->hasActiveLeague()
            ->with(['league.category', 'teamOne', 'teamTwo', 'markets' => function ($m) {
                $m->active()->unLocked()->resultUndeclared()->with(['outcomes' => function ($o) {
                    $o->active()->unLocked();
                }]);
            }]);

        if ($categorySlug !== 'all') {
            $query->whereHas('league.category', function ($cat) use ($categorySlug) {
                $cat->where('slug', $categorySlug);
            });
        }

        if ($term = trim($request->input('q', ''))) {
            $query->where('title', 'like', "%{$term}%");
        }

        $tab = $request->input('tab', 'open');
        if ($tab === 'inplay') {
            $query->where('start_time', '<=', now());
        } elseif ($tab === 'today') {
            $query->whereBetween('start_time', [now(), now()->endOfDay()]);
        } else {
            $query->where('start_time', '>', now());
        }

        $games = $query->orderBy('start_time')->get();
        $sessionSlip = session()->get('sports_betslip', []);

        return view('frontend.Minimal.sports.index', compact('categories', 'games', 'categorySlug', 'tab', 'term', 'sessionSlip'));
    }

    public function addToBetslip(Request $request)
    {
        $request->validate([
            'outcome_id' => 'required|integer|exists:sports_outcomes,id'
        ]);

        $outcome = Outcome::availableForBet()->with('market.game.league.category')->find($request->outcome_id);
        if (!$outcome) {
            return response()->json(['error' => 'This selection is no longer available.'], 422);
        }

        $slip = session()->get('sports_betslip', []);

        if (isset($slip[$outcome->id])) {
            return response()->json(['error' => 'Selection already in betslip.'], 422);
        }

        foreach ($slip as $item) {
            if ($item['market_id'] == $outcome->market_id) {
                return response()->json(['error' => 'You cannot select multiple outcomes from the same match.'], 422);
            }
        }

        $slip[$outcome->id] = [
            'outcome_id' => $outcome->id,
            'outcome_name' => $outcome->name,
            'odds' => (float)$outcome->odds,
            'market_id' => $outcome->market_id,
            'market_title' => $outcome->market->market_title,
            'game_id' => $outcome->market->game_id,
            'game_title' => $outcome->market->game->title,
            'stake_amount' => 10.0,
        ];

        session()->put('sports_betslip', $slip);

        return response()->json([
            'success' => 'Added to betslip.',
            'html' => view('frontend.Minimal.sports.betslip_item', ['item' => $slip[$outcome->id]])->render(),
            'slipCount' => count($slip)
        ]);
    }

    public function removeFromBetslip(Request $request)
    {
        $request->validate([
            'outcome_id' => 'required|integer'
        ]);

        $slip = session()->get('sports_betslip', []);
        if (isset($slip[$request->outcome_id])) {
            unset($slip[$request->outcome_id]);
            session()->put('sports_betslip', $slip);
        }

        return response()->json([
            'success' => 'Removed from betslip.',
            'slipCount' => count($slip)
        ]);
    }

    public function clearBetslip()
    {
        session()->forget('sports_betslip');
        return response()->json([
            'success' => 'Betslip cleared.',
            'slipCount' => 0
        ]);
    }

    public function placeBet(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['error' => 'Please log in to place bets.'], 401);
        }

        $request->validate([
            'type' => 'required|in:1,2',
            'multi_stake' => 'nullable|numeric|min:0.01',
            'stakes' => 'nullable|array'
        ]);

        $slip = session()->get('sports_betslip', []);
        if (empty($slip)) {
            return response()->json(['error' => 'Betslip is empty.'], 422);
        }

        $type = (int)$request->input('type');
        $user = Auth::user();

        try {
            $items = [];
            if ($type === 1) {
                $stakes = $request->input('stakes', []);
                foreach ($slip as $outcomeId => $item) {
                    $stake = isset($stakes[$outcomeId]) ? (float)$stakes[$outcomeId] : 0.0;
                    if ($stake <= 0) {
                        throw new \Exception("Please enter a valid stake for each selection.");
                    }
                    $items[] = [
                        'outcome_id' => $outcomeId,
                        'stake_amount' => $stake
                    ];
                }
                $bet = $this->betService->placeBet($user, $items, 1);
            } else {
                $multiStake = (float)$request->input('multi_stake', 0.0);
                foreach ($slip as $outcomeId => $item) {
                    $items[] = [
                        'outcome_id' => $outcomeId,
                        'stake_amount' => $multiStake
                    ];
                }
                $bet = $this->betService->placeBet($user, $items, 2, $multiStake);
            }

            session()->forget('sports_betslip', []);

            return response()->json([
                'success' => 'Bet placed successfully!',
                'balance' => number_format($user->balance, 2)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}
