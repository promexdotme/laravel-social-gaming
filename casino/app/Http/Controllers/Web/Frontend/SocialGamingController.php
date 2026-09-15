<?php

namespace VanguardLTE\Http\Controllers\Web\Frontend;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use VanguardLTE\User;
use VanguardLTE\LottoGame;
use VanguardLTE\LottoTicket;
use VanguardLTE\LottoDraw;

class SocialGamingController extends Controller
{
    /**
     * Display Dynamic Multi-Draw Lotto Hub
     */
    public function lotto(Request $request)
    {
        $user = Auth::user();
        
        $activeGames = LottoGame::where('is_active', true)->get();
        $selectedGameSlug = $request->input('game', 'daily-lucky-4');
        
        $currentGame = LottoGame::where('slug', $selectedGameSlug)->first() ?? $activeGames->first();
        
        $recentDraws = [];
        $userTickets = [];

        if ($currentGame) {
            $recentDraws = LottoDraw::where('lotto_game_id', $currentGame->id)
                ->orderBy('id', 'desc')
                ->take(5)
                ->get();

            if ($user) {
                $userTickets = LottoTicket::where('lotto_game_id', $currentGame->id)
                    ->where('user_id', $user->id)
                    ->orderBy('id', 'desc')
                    ->take(10)
                    ->get();
            }
        }

        $recentWinners = [
            ['user' => 'LuckySpin99', 'coins' => '250,000', 'time' => '10 mins ago'],
            ['user' => 'CryptoKing', 'coins' => '1,000,000', 'time' => '1 hour ago'],
            ['user' => 'VegasPro777', 'coins' => '50,000', 'time' => '3 hours ago']
        ];

        return view('frontend.Minimal.lotto.index', compact(
            'user', 
            'activeGames', 
            'currentGame', 
            'recentDraws', 
            'userTickets', 
            'recentWinners'
        ));
    }

    /**
     * Submit Multi-Draw Lotto Ticket Entry
     */
    public function lottoPlay(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please log in to purchase lotto tickets!']);
        }

        $gameId = $request->input('lotto_game_id') ?: $request->input('game_id');
        $game = LottoGame::find($gameId);

        if (!$game || !$game->is_active) {
            return response()->json(['success' => false, 'message' => 'Selected Lotto Game is not active!']);
        }

        $numbers = $request->input('numbers', []);
        if (!is_array($numbers) || count($numbers) !== $game->pick_count) {
            return response()->json([
                'success' => false, 
                'message' => "Please select exactly {$game->pick_count} numbers for {$game->title}!"
            ]);
        }

        // Validate range
        foreach ($numbers as $num) {
            $val = (int)$num;
            if ($val < 1 || $val > $game->max_number) {
                return response()->json([
                    'success' => false,
                    'message' => "Invalid number {$num}. Numbers must be between 1 and {$game->max_number}!"
                ]);
            }
        }

        // Check entry fee balance
        if ($user->balance < $game->entry_fee) {
            return response()->json([
                'success' => false,
                'message' => "Insufficient Cedar Coins! Required entry fee is " . number_format($game->entry_fee, 0) . " Coins."
            ]);
        }

        // Deduct entry fee
        $user->decrement('balance', $game->entry_fee);
        \VanguardLTE\Services\AffiliateService::recordWagerCommission($user, $game->entry_fee, 'lotto');
        \VanguardLTE\Services\VipService::recordWagerXpAndRakeback($user, $game->entry_fee, 5.0);

        sort($numbers);

        // Create Ticket
        $ticket = LottoTicket::create([
            'lotto_game_id' => $game->id,
            'user_id' => $user->id,
            'numbers_json' => $numbers,
            'draw_date' => now()->format('Y-m-d'),
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Ticket Registered for '{$game->title}'! Good luck!",
            'ticket_id' => $ticket->id,
            'numbers' => $numbers,
            'new_balance' => number_format($user->balance, 0)
        ]);
    }

    /**
     * Display Political & Event Predictions Page
     */
    public function predictions()
    {
        $user = Auth::user();
        
        $predictionMarkets = [
            [
                'id' => 101,
                'category' => 'GEOPOLITICS',
                'title' => 'Will AI Humanoid Robots enter retail homes before 2027?',
                'description' => 'Market resolves YES if at least one commercial humanoid robot achieves 10,000+ consumer home sales before Dec 31, 2026.',
                'yes_percent' => 42,
                'yes_odds' => 2.38,
                'no_odds' => 1.72,
                'total_bets' => '250,000 CEDARS'
            ],
            [
                'id' => 102,
                'category' => 'TECH & SPACE',
                'title' => 'Will Starship achieve uncrewed Mars landing before 2027?',
                'description' => 'Resolves YES if SpaceX lands a Starship vessel payload on Mars surface before Jan 1, 2027.',
                'yes_percent' => 64,
                'yes_odds' => 1.56,
                'no_odds' => 2.75,
                'total_bets' => '500,000 CEDARS'
            ]
        ];

        return view('frontend.Minimal.predictions.index', compact('user', 'predictionMarkets'));
    }

    /**
     * Cast Free Prediction Market Vote
     */
    public function predictionsVote(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Please log in to vote!']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Prediction Vote Registered Successfully!',
            'new_balance' => number_format($user->balance, 0)
        ]);
    }

    /**
     * Refill Free Cedar Coins for User or Guest
     */
    public function refillCoins(Request $request)
    {
        $amount = (float) (function_exists('settings') ? settings('default_refill_amount', 50000) : 50000);
        $user = Auth::user();
        if ($user) {
            $user->increment('balance', $amount);
            return response()->json([
                'success' => true,
                'message' => number_format($amount, 0) . ' Cedar Coins added to your account! 🎉',
                'balance' => number_format($user->balance, 0)
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Guest account refilled with ' . number_format($amount, 0) . ' Cedar Coins! 🎉',
            'balance' => number_format($amount, 0)
        ]);
    }
}
