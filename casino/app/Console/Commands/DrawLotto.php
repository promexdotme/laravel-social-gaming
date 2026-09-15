<?php

namespace VanguardLTE\Console\Commands;

use Illuminate\Console\Command;
use VanguardLTE\LottoGame;
use VanguardLTE\LottoTicket;
use VanguardLTE\LottoDraw;
use VanguardLTE\User;
use Illuminate\Support\Facades\Log;

class DrawLotto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'casino:draw-lotto {--game= : Specific lotto game slug}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute automated draw for active multi-draw lotto games';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $gameSlug = $this->option('game');
        $query = LottoGame::where('is_active', true);

        if ($gameSlug) {
            $query->where('slug', $gameSlug);
        }

        $games = $query->get();

        if ($games->isEmpty()) {
            $this->info('No active lotto games found.');
            return 0;
        }

        foreach ($games as $game) {
            $this->processGameDraw($game);
        }

        $this->info('Multi-Draw Lotto Execution Complete!');
        return 0;
    }

    protected function processGameDraw(LottoGame $game)
    {
        // 1. Generate unique winning numbers
        $winningNumbers = [];
        while (count($winningNumbers) < $game->pick_count) {
            $rand = mt_rand(1, $game->max_number);
            if (!in_array($rand, $winningNumbers)) {
                $winningNumbers[] = $rand;
            }
        }
        sort($winningNumbers);

        $today = now()->format('Y-m-d');

        // 2. Fetch pending tickets for this game (any ticket up to today)
        $tickets = LottoTicket::where('lotto_game_id', $game->id)
            ->where('status', 'pending')
            ->where('draw_date', '<=', $today)
            ->get();

        $totalTickets = $tickets->count();
        $totalWinners = 0;
        $totalPaid = 0.00;

        foreach ($tickets as $ticket) {
            $userNumbers = is_array($ticket->numbers_json) ? $ticket->numbers_json : json_decode($ticket->numbers_json, true);
            $matches = array_intersect($winningNumbers, $userNumbers);
            $matchCount = count($matches);

            $payout = 0.00;

            // Multi-Tier Prize Distribution Pool
            if ($matchCount === $game->pick_count) {
                // Tier 1: 100% Grand Jackpot Win
                $payout = (float)$game->jackpot_pool;
            } elseif ($matchCount === ($game->pick_count - 1) && $game->pick_count >= 4) {
                // Tier 2: Match (K - 1) -> 10% of Jackpot Pool or 500x entry fee
                $payout = max(round($game->jackpot_pool * 0.10, 2), (float)$game->entry_fee * 500);
            } elseif ($matchCount === ($game->pick_count - 2) && $game->pick_count >= 5) {
                // Tier 3: Match (K - 2) -> 50x entry fee
                $payout = round((float)$game->entry_fee * 50, 2);
            } elseif ($matchCount === ($game->pick_count - 3) && $game->pick_count >= 6) {
                // Tier 4: Match (K - 3) -> 5x entry fee
                $payout = round((float)$game->entry_fee * 5, 2);
            }

            $ticket->matches_count = $matchCount;

            if ($payout > 0) {
                $ticket->status = 'won';
                $ticket->payout_amount = $payout;
                $totalWinners++;
                $totalPaid += $payout;

                $user = User::find($ticket->user_id);
                if ($user) {
                    $user->increment('balance', $payout);
                    Log::info("[Lotto Draw] User ID {$user->id} WON {$payout} Cedars in Game: {$game->title} (Matched {$matchCount}/{$game->pick_count})");
                }
            } else {
                $ticket->status = 'lost';
            }

            $ticket->save();
        }

        if (function_exists('settings')) {
            settings()->set('last_lotto_draw_at', now()->toDateTimeString());
            settings()->save();
        }

        // 3. Store Draw History Log
        LottoDraw::create([
            'lotto_game_id' => $game->id,
            'winning_numbers_json' => $winningNumbers,
            'draw_date' => $today,
            'total_tickets' => $totalTickets,
            'total_winners' => $totalWinners,
            'total_paid' => $totalPaid,
            'drawn_at' => now(),
        ]);

        $this->info("Draw Executed for '{$game->title}'! Winning Numbers: [" . implode(', ', $winningNumbers) . "] | Total Tickets: {$totalTickets} | Winners: {$totalWinners} | Total Paid: {$totalPaid}");
    }
}
