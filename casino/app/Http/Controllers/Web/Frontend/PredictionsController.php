<?php

namespace VanguardLTE\Http\Controllers\Web\Frontend;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VanguardLTE\User;
use VanguardLTE\PredictionMarket;
use VanguardLTE\PredictionVote;
use VanguardLTE\Services\PolymarketService;
use Carbon\Carbon;

class PredictionsController extends Controller
{
    protected $polyService;

    public function __construct(PolymarketService $polyService)
    {
        $this->polyService = $polyService;
    }

    /**
     * Display Prediction Markets Arena
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $selectedCategory = $request->input('category', 'all');

        $query = PredictionMarket::where('status', 'active')
            ->where('end_date', '>', now());

        if ($selectedCategory !== 'all') {
            $query->where('category', $selectedCategory);
        }

        $activeMarkets = $query->orderBy('id', 'desc')->get();

        $userVotes = [];
        if ($user) {
            $userVotes = PredictionVote::where('user_id', $user->id)
                ->orderBy('id', 'desc')
                ->take(10)
                ->get();
        }

        return view('frontend.Minimal.predictions.index', compact('user', 'activeMarkets', 'selectedCategory', 'userVotes'));
    }

    /**
     * Hybrid Search (Prioritizes local DB markets first, then external Polymarket)
     */
    public function search(Request $request)
    {
        $keyword = trim($request->input('q', ''));

        // 1. Search local DB markets first
        $localQuery = PredictionMarket::where('status', 'active')
            ->where('end_date', '>', now());

        if (!empty($keyword)) {
            $localQuery->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            });
        }

        $localMarkets = $localQuery->take(10)->get();
        $results = [];

        foreach ($localMarkets as $lm) {
            $results[] = [
                'market_id' => $lm->market_id,
                'title' => $lm->title,
                'description' => $lm->description,
                'category' => $lm->category,
                'yes_price' => $lm->yes_price,
                'no_price' => $lm->no_price,
                'yes_percent' => $lm->yes_percent,
                'no_percent' => $lm->no_percent,
                'yes_odds' => $lm->yes_odds,
                'no_odds' => $lm->no_odds,
                'total_volume' => $lm->total_volume,
                'end_date' => $lm->end_date->toIso8601String(),
                'is_local' => true,
            ];
        }

        // 2. Fetch external Polymarket results
        try {
            $externalMarkets = $this->polyService->searchEvents($keyword);
            foreach ($externalMarkets as $em) {
                $existsLocally = PredictionMarket::where('market_id', $em['market_id'])->exists();
                if (!$existsLocally) {
                    $results[] = $em;
                }
            }
        } catch (\Throwable $e) {
            // Polymarket API fallback
        }

        return response()->json([
            'success' => true,
            'count' => count($results),
            'data' => $results
        ]);
    }

    /**
     * Return Real-time Simulated Order Book Depth for Market
     */
    public function orderBook($marketId)
    {
        $market = PredictionMarket::where('market_id', $marketId)->first();
        if (!$market) {
            return response()->json(['success' => false, 'message' => 'Market not found'], 404);
        }

        return response()->json([
            'success' => true,
            'market_id' => $market->market_id,
            'title' => $market->title,
            'yes_price' => $market->yes_price,
            'no_price' => $market->no_price,
            'yes_percent' => $market->yes_percent,
            'no_percent' => $market->no_percent,
            'total_volume' => $market->total_volume,
            'order_book' => $market->order_book,
        ]);
    }

    /**
     * Acquire Probability Shares on Prediction Market
     */
    public function vote(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please log in to acquire prediction shares!'], 401);
        }

        $marketId = $request->input('market_id');
        $choice = strtolower(trim($request->input('choice', 'yes'))); // 'yes', 'no'
        $stake = (float) $request->input('stake', 0);

        if (!in_array($choice, ['yes', 'no'])) {
            return response()->json(['success' => false, 'message' => 'Invalid choice. Must be YES or NO.']);
        }

        if ($stake <= 0) {
            return response()->json(['success' => false, 'message' => 'Please enter a valid stake amount!']);
        }

        if ($user->balance < $stake) {
            return response()->json(['success' => false, 'message' => 'Insufficient Cedar Coins balance!']);
        }

        // Check or Auto-Clone Market
        $market = PredictionMarket::where('market_id', $marketId)->first();

        if (!$market) {
            $title = $request->input('title', 'Prediction Market');
            $description = $request->input('description', '');
            $category = $request->input('category', 'geopolitics');
            $endDate = $request->input('end_date', now()->addDays(30)->toIso8601String());

            // Initialize AMM pool proportional to the live market probability (Total initial liquidity = 2,000 Cedar Coins)
            $initialProb = (float) $request->input('initial_price', 0.50);
            $initialProb = max(0.01, min(0.99, $initialProb));
            $initialTotalPool = 2000.00;
            $initPoolYes = round($initialTotalPool * $initialProb, 2);
            $initPoolNo = round($initialTotalPool * (1.00 - $initialProb), 2);

            $market = PredictionMarket::create([
                'market_id' => $marketId,
                'title' => $title,
                'description' => $description,
                'category' => $category,
                'pool_yes' => max(10.0, $initPoolYes),
                'pool_no' => max(10.0, $initPoolNo),
                'end_date' => Carbon::parse($endDate)->setTimezone('UTC'),
                'status' => 'active',
            ]);
        }

        if ($market->status !== 'active' || now()->gte($market->end_date)) {
            return response()->json(['success' => false, 'message' => 'This prediction market is closed for trading!']);
        }

        // Calculate Probability Shares & Payout
        $sharePrice = ($choice === 'yes') ? $market->yes_price : $market->no_price;
        $odds = ($choice === 'yes') ? $market->yes_odds : $market->no_odds;
        $sharesCount = round($stake / $sharePrice, 2);
        $potentialWin = round($sharesCount * 1.00, 2);
        $profit = round($potentialWin - $stake, 2);
        $roi = round(($profit / $stake) * 100, 1);

        // Update AMM Liquidity Pool
        if ($choice === 'yes') {
            $market->increment('pool_yes', $stake);
        } else {
            $market->increment('pool_no', $stake);
        }

        // Deduct Stake
        $user->decrement('balance', $stake);
        \VanguardLTE\Services\AffiliateService::recordWagerCommission($user, $stake, 'predictions');
        \VanguardLTE\Services\VipService::recordWagerXpAndRakeback($user, $stake, 2.0);

        $vote = PredictionVote::create([
            'user_id' => $user->id,
            'market_id' => $market->market_id,
            'choice' => $choice,
            'odds' => $odds,
            'share_price' => $sharePrice,
            'shares_count' => $sharesCount,
            'stake' => $stake,
            'potential_win' => $potentialWin,
            'status' => 'pending',
        ]);

        $market->refresh();

        return response()->json([
            'success' => true,
            'message' => "Acquired " . number_format($sharesCount, 2) . " " . strtoupper($choice) . " Shares @ " . number_format($sharePrice * 100, 0) . "¢! Potential Payout: " . number_format($potentialWin, 0) . " Cedar Coins (+{$roi}% ROI)",
            'shares_count' => $sharesCount,
            'share_price' => $sharePrice,
            'potential_win' => $potentialWin,
            'roi_percent' => $roi,
            'vote_id' => $vote->id,
            'new_yes_price' => $market->yes_price,
            'new_no_price' => $market->no_price,
            'new_yes_percent' => $market->yes_percent,
            'new_no_percent' => $market->no_percent,
            'new_balance' => number_format($user->balance, 0),
        ]);
    }

    /**
     * Create Custom Player Prediction Market ("Create Your Own Bet")
     */
    public function createCustomMarket(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please log in to create custom bets!'], 401);
        }

        $title = trim($request->input('title', ''));
        $description = trim($request->input('description', ''));
        $category = $request->input('category', 'custom');
        $endDate = $request->input('end_date');
        $verificationUrl = trim($request->input('verification_url', ''));
        $choice = strtolower(trim($request->input('choice', 'yes')));
        $initialStake = (float) $request->input('initial_stake', 1000);

        if (empty($title) || empty($endDate)) {
            return response()->json(['success' => false, 'message' => 'Market title and resolution date are required!']);
        }

        if ($initialStake <= 0) {
            return response()->json(['success' => false, 'message' => 'Initial seed stake must be greater than zero!']);
        }

        if ($user->balance < $initialStake) {
            return response()->json(['success' => false, 'message' => 'Insufficient Cedar Coins for initial seed stake!']);
        }

        $customSlug = 'custom_' . time() . '_' . rand(100, 999);

        // Seed initial probability pools
        $poolYes = 1000.00;
        $poolNo = 1000.00;

        if ($choice === 'yes') {
            $poolYes += $initialStake;
        } else {
            $poolNo += $initialStake;
        }

        $market = PredictionMarket::create([
            'market_id' => $customSlug,
            'creator_id' => $user->id,
            'category' => $category,
            'title' => $title,
            'description' => $description,
            'verification_url' => $verificationUrl,
            'pool_yes' => $poolYes,
            'pool_no' => $poolNo,
            'end_date' => Carbon::parse($endDate)->setTimezone('UTC'),
            'status' => 'active',
        ]);

        $sharePrice = ($choice === 'yes') ? $market->yes_price : $market->no_price;
        $odds = ($choice === 'yes') ? $market->yes_odds : $market->no_odds;
        $sharesCount = round($initialStake / $sharePrice, 2);
        $potentialWin = round($sharesCount * 1.00, 2);

        // Deduct initial seed stake
        $user->decrement('balance', $initialStake);

        PredictionVote::create([
            'user_id' => $user->id,
            'market_id' => $market->market_id,
            'choice' => $choice,
            'odds' => $odds,
            'share_price' => $sharePrice,
            'shares_count' => $sharesCount,
            'stake' => $initialStake,
            'potential_win' => $potentialWin,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Custom Prediction Market Created! Your position of " . number_format($sharesCount, 0) . " shares is active.",
            'market_id' => $market->market_id,
            'new_balance' => number_format($user->balance, 0),
        ]);
    }
}
