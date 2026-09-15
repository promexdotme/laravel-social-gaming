<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use VanguardLTE\Http\Controllers\Controller;
use Illuminate\Http\Request;
use VanguardLTE\LottoGame;
use VanguardLTE\LottoTicket;
use VanguardLTE\LottoDraw;
use VanguardLTE\User;

class LottoController extends Controller
{
    /**
     * Display Lotto Games & Ticket Management
     */
    public function index()
    {
        $games = LottoGame::orderBy('id', 'desc')->get();
        $tickets = LottoTicket::with('user', 'game')->orderBy('id', 'desc')->take(30)->get();
        $draws = LottoDraw::with('game')->orderBy('id', 'desc')->take(10)->get();

        return view('liteback.lotto.index', compact('games', 'tickets', 'draws'));
    }

    /**
     * Create New Lotto Game Rule
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'max_number' => 'required|integer|min:10|max:99',
            'pick_count' => 'required|integer|min:3|max:10',
            'entry_fee' => 'required|numeric|min:0',
        ]);

        $slug = \Str::slug($request->input('title'));

        LottoGame::create([
            'title' => $request->input('title'),
            'slug' => $slug,
            'description' => $request->input('description', 'Official Social Lotto Draw'),
            'max_number' => $request->input('max_number'),
            'pick_count' => $request->input('pick_count'),
            'entry_fee' => $request->input('entry_fee'),
            'jackpot_pool' => $request->input('jackpot_pool', 1000000),
            'draw_schedule' => $request->input('draw_schedule', 'Daily 21:00 UTC'),
            'is_active' => true,
        ]);

        return redirect()->route('liteback.lotto.index')->with('success', 'Lotto Game created successfully!');
    }

    /**
     * Toggle Active State of Lotto Game
     */
    public function toggle(Request $request, $game)
    {
        if (!$game instanceof LottoGame) {
            $game = LottoGame::find($game);
        }

        if (!$game) {
            return redirect()->route('liteback.lotto.index')->with('error', 'Lotto Game not found!');
        }

        $game->is_active = !$game->is_active;
        $game->save();

        return redirect()->route('liteback.lotto.index')->with('success', "Lotto Game '{$game->title}' status updated!");
    }

    /**
     * Trigger Manual Draw & Winning Numbers Settlement
     */
    public function draw(Request $request, $game)
    {
        if (!$game instanceof LottoGame) {
            $game = LottoGame::find($game);
        }

        if (!$game) {
            return redirect()->route('liteback.lotto.index')->with('error', 'Lotto Game not found!');
        }

        // Generate random winning numbers for game pick_count
        $winningNumbers = [];
        while (count($winningNumbers) < $game->pick_count) {
            $num = rand(1, $game->max_number);
            if (!in_array($num, $winningNumbers)) {
                $winningNumbers[] = $num;
            }
        }
        sort($winningNumbers);

        // Settle Pending Tickets
        $pendingTickets = LottoTicket::where('lotto_game_id', $game->id)
            ->where('status', 'pending')
            ->get();

        // Record Draw
        $draw = LottoDraw::create([
            'lotto_game_id' => $game->id,
            'winning_numbers_json' => $winningNumbers,
            'draw_date' => now()->format('Y-m-d H:i:s'),
            'drawn_at' => now(),
            'total_tickets' => count($pendingTickets),
            'total_winners' => 0,
            'total_paid' => 0,
            'jackpot_paid' => 0,
        ]);

        $totalPaidOut = 0;
        $totalWinners = 0;
        foreach ($pendingTickets as $ticket) {
            $pick = is_array($ticket->numbers_json) ? $ticket->numbers_json : (json_decode($ticket->numbers_json, true) ?? []);
            $matches = count(array_intersect($pick, $winningNumbers));

            $ticket->matches_count = $matches;

            if ($matches === $game->pick_count) {
                // Jackpot Winner!
                $prize = $game->jackpot_pool;
                $ticket->status = 'jackpot_win';
                $ticket->payout_amount = $prize;
                $ticket->prize_won = $prize;
                $ticket->save();

                User::where('id', $ticket->user_id)->increment('balance', $prize);
                $totalPaidOut += $prize;
                $totalWinners++;
            } elseif ($matches >= floor($game->pick_count / 2)) {
                // Partial Match Winner!
                $prize = $game->entry_fee * 5;
                $ticket->status = 'win';
                $ticket->payout_amount = $prize;
                $ticket->prize_won = $prize;
                $ticket->save();

                User::where('id', $ticket->user_id)->increment('balance', $prize);
                $totalPaidOut += $prize;
                $totalWinners++;
            } else {
                $ticket->status = 'lost';
                $ticket->payout_amount = 0;
                $ticket->prize_won = 0;
                $ticket->save();
            }
        }

        $draw->total_winners = $totalWinners;
        $draw->total_paid = $totalPaidOut;
        $draw->jackpot_paid = $totalPaidOut;
        $draw->save();

        $winningStr = implode(', ', $winningNumbers);
        return redirect()->route('liteback.lotto.index')->with('success', "Draw executed for '{$game->title}'! Winning Numbers: [{$winningStr}]. Total Payout: " . number_format($totalPaidOut, 0) . " Cedar Coins.");
    }
}
