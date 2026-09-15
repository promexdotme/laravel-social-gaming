<?php

namespace VanguardLTE\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use VanguardLTE\Game;
use VanguardLTE\Games\CedarMath;
use VanguardLTE\Games\CedarTables;
use VanguardLTE\StatGame;
use VanguardLTE\User;

/** All state transitions share the wallet lock and the database transaction. */
class CedarGameService
{
    public const GAMES = ['CedarDice', 'CedarWheel', 'CedarPlinko', 'CedarMines', 'CedarCrash', 'RoyalSteps'];
    private User $user;
    private string $game;
    private array $state;
    private array $rules;

    public function settleCrashFor(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            $this->game = 'CedarCrash';
            $this->user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
            $row = DB::table('cedar_states')->where('user_id', $userId)->where('game', $this->game)->first();
            if (!$row) return;
            $this->state = json_decode($row->state, true, 512, JSON_THROW_ON_ERROR);
            $this->rules = $this->rules($this->game);
            $round = $this->active();
            if (!$round) return;
            $this->crashAction($round, 'status');
            DB::table('cedar_states')->where('id', $row->id)->update(['state' => json_encode($this->state, JSON_THROW_ON_ERROR)]);
        }, 5);
    }

    public function handle(Request $request, string $game): array
    {
        if (!in_array($game, self::GAMES, true)) return $this->error('Unknown Cedar game.');
        if (!Auth::check()) return $this->error('Sign in to play Cedar Originals.');
        try {
            return DB::transaction(function () use ($request, $game) {
                $this->game = $game;
                $this->user = User::whereKey(Auth::id())->lockForUpdate()->firstOrFail();
                if ($this->user->is_blocked || $this->user->status !== 'Active') {
                    throw new \DomainException('This account cannot play.');
                }
                $action = $request->input('action', 'init');
                if (!is_string($action)) throw new \DomainException('Invalid action.');
                $this->rules = $this->rules($game);
                $row = DB::table('cedar_states')->where('user_id', $this->user->id)->where('game', $game)->first();
                $this->state = $row ? json_decode($row->state, true, 512, JSON_THROW_ON_ERROR) : $this->newState();
                if (!$row) $this->refundLegacyRound();
                $result = $this->dispatch($request, $action);
                DB::table('cedar_states')->updateOrInsert(
                    ['user_id' => $this->user->id, 'game' => $game],
                    ['state' => json_encode($this->state, JSON_THROW_ON_ERROR)]
                );
                return $result;
            }, 5);
        } catch (\DomainException $e) {
            return $this->error($e->getMessage());
        }
    }

    private function error(string $message): array { return ['status' => 'error', 'message' => $message]; }
    private function setting(string $key, $default)
    {
        $value = function_exists('settings') ? settings($key, $default) : $default;
        return is_numeric($default) && (!is_scalar($value) || !is_numeric($value) || !is_finite((float) $value)) ? $default : $value;
    }
    private function balance(): string { return number_format((float) $this->user->balance, 2, '.', ''); }

    private function rules(string $game): array
    {
        $slug = $game === 'RoyalSteps' ? 'royal_steps' : strtolower(substr($game, 5));
        $edge = 5.0;
        return [
            'min_bet' => max(0.01, (float) $this->setting("cedar_{$slug}_min_bet", 10)),
            'max_bet' => max(0.01, min(1000000, (float) $this->setting("cedar_{$slug}_max_bet", 50000))),
            'house_edge' => max(0.1, min(20, (float) $this->setting("cedar_{$slug}_house_edge", $edge))),
            'max_multiplier' => max(2, min(10000, (float) $this->setting('cedar_crash_max_multiplier', 1000))),
            'max_payout' => max(1, min(100000000, (float) $this->setting('cedar_max_payout', 1000000))),
        ];
    }

    private function newState(): array
    {
        return ['server_seed' => bin2hex(random_bytes(32)), 'client_seed' => bin2hex(random_bytes(8)), 'nonce' => 1, 'active' => null];
    }

    private function refundLegacyRound(): void
    {
        if (!in_array($this->game, ['CedarCrash', 'CedarMines'], true)) return;
        $key = 'cedar_' . strtolower(substr($this->game, 5)) . '_active_' . $this->user->id;
        $old = Cache::get($key);
        if (!$old || !empty($old['settled'])) return;
        $wager = $old['wager'] ?? 0;
        if (!is_numeric($wager) || !is_finite((float) $wager) || $wager <= 0 || $wager > 1000000) {
            throw new \DomainException('Legacy round needs administrator review.');
        }
        $this->user->increment('balance', $wager);
        $this->audit(0, (float) $wager);
        $this->saveRound(['id' => (string) Str::uuid(), 'user_id' => $this->user->id, 'game' => $this->game,
            'request_id' => 'legacy-upgrade-refund', 'status' => 'void', 'wager' => $wager, 'win' => $wager,
            'data' => ['reason' => 'Unfinished legacy round refunded during cedar-v2 upgrade.']], true);
        // State creation and refund commit together; later requests never refund again.
        DB::afterCommit(fn () => Cache::forget($key));
    }

    private function commitment(): array
    {
        return ['server_seed_hash' => hash('sha256', $this->state['server_seed']), 'client_seed' => $this->state['client_seed'], 'nonce' => $this->state['nonce']];
    }

    private function dispatch(Request $request, string $action): array
    {
        if ($action === 'init') {
            $active = $this->active();
            $activeData = null;
            if ($active) {
                $activeData = $this->game === 'CedarCrash'
                    ? $this->crashAction($active, 'status') : ($this->game === 'RoyalSteps' ? $this->stepsView($active) : $this->minesView($active));
            }
            $history = DB::table('cedar_rounds')->where('user_id', $this->user->id)->where('game', $this->game)
                ->where('status', 'settled')->orderByDesc('created_at')->limit(20)->get();
            $lastProof = $history->isNotEmpty() ? (json_decode($history->first()->data, true)['result']['proof'] ?? null) : null;
            $history = $history->map(function ($row) {
                $d = json_decode($row->data, true);
                return $this->game === 'CedarCrash' ? ($d['crash_multiplier'] ?? 1) : ($d['result'] ?? []);
            })->all();
            return array_merge(['status' => 'success', 'balance' => $this->balance(), 'currency' => 'CEDARS',
                'configurations' => CedarMath::wheel(), 'multipliers' => CedarTables::PLINKO,
                'step_ladder' => CedarMath::STEPS, 'default_rows' => 16, 'default_risk' => 'medium', 'history' => $history, 'last_proof' => $lastProof,
                'has_active_game' => (bool) $this->state['active'], 'active_game' => $activeData,
                'math_version' => CedarMath::VERSION], $this->rules, $this->commitment());
        }
        if ($action === 'verify') {
            // The supplied round must belong to this account and already be settled.
            $round = $this->findRound($this->roundId($request));
            if (!$round || $round['status'] !== 'settled') throw new \DomainException('Select a completed round to verify.');
            return ['status' => 'success', 'proof' => $this->proof($round)];
        }
        if (in_array($action, ['roll', 'spin', 'drop', 'bet'], true)) return $this->bet($request, $action);
        $round = $this->findRound($this->roundId($request));
        if (!$round) throw new \DomainException('Round not found. Reload the game to recover your round.');
        if ($round['status'] === 'settled') return $round['data']['result'];
        if ($this->game === 'CedarCrash' && in_array($action, ['status', 'cashout', 'crash'], true)) {
            return $this->crashAction($round, $action);
        }
        if ($this->game === 'CedarMines' && in_array($action, ['reveal', 'cashout'], true)) {
            return $this->minesAction($round, $request, $action);
        }
        if ($this->game === 'RoyalSteps' && in_array($action, ['step', 'cashout', 'status'], true)) return $this->stepsAction($round, $request, $action);
        throw new \DomainException('Invalid action.');
    }

    private function bet(Request $request, string $action): array
    {
        $expected = ['CedarDice' => 'roll', 'CedarWheel' => 'spin', 'CedarPlinko' => 'drop', 'CedarMines' => 'bet', 'CedarCrash' => 'bet', 'RoyalSteps' => 'bet'];
        if ($expected[$this->game] !== $action) throw new \DomainException('Invalid bet action.');
        $id = $request->input('request_id');
        if (!is_string($id) || !preg_match('/^[a-zA-Z0-9_-]{16,64}$/D', $id)) throw new \DomainException('Missing request ID. Reload the game.');
        $previous = DB::table('cedar_rounds')->where('user_id', $this->user->id)->where('game', $this->game)->where('request_id', $id)->first();
        if ($previous) {
            $old = $this->decode($previous);
            return $old['data'][$old['status'] === 'settled' ? 'result' : 'bet_result'];
        }
        if ($this->active()) throw new \DomainException('Finish the current round first.');
        if ((string) $this->setting('enable_cedar_originals', '1') !== '1' ||
            !Game::where('name', $this->game)->where('shop_id', $this->user->shop_id ?: 1)->where('view', 1)->exists()) {
            throw new \DomainException('This game is currently disabled. Existing rounds can still be settled.');
        }
        $commit = $request->input('server_seed_hash');
        if (!is_string($commit) || !hash_equals(hash('sha256', $this->state['server_seed']), $commit)) {
            throw new \DomainException('Seed changed. Reload the game before betting.');
        }
        $wager = $this->number($request->input('wager'), $this->rules['min_bet'], $this->rules['max_bet']);
        if (abs($wager * 100 - round($wager * 100)) > 0.00001) throw new \DomainException('Use at most two decimal places for wagers.');
        if ((float) $this->user->balance < $wager) throw new \DomainException('Insufficient Cedar Coins balance.');
        $client = $request->input('client_seed', $this->state['client_seed']);
        if (!is_string($client) || !preg_match('/^[a-zA-Z0-9_-]{1,128}$/D', $client)) throw new \DomainException('Client seed must contain 1–128 letters, numbers, underscores or hyphens.');
        $d = ['server_seed' => $this->state['server_seed'], 'client_seed' => $client, 'nonce' => $this->state['nonce'],
            'rules' => $this->rules, 'math_version' => CedarMath::VERSION];
        $sample = fn ($size, $cursor = 0) => CedarMath::integer($d['server_seed'], $client, $d['nonce'], $size, $cursor);
        $result = ['status' => 'success'];
        $win = 0;
        $edge = $this->rules['house_edge'];
        switch ($this->game) {
            case 'RoyalSteps':
                $d += ['step' => 0, 'trap_step' => CedarMath::stepsTrap($d['server_seed'], $client, $d['nonce'], $edge)];
                $d['parameters'] = ['ladder' => CedarMath::STEPS];
                $result += ['step' => 0, 'multiplier' => 1, 'current_win' => 0];
                break;
            case 'CedarDice':
                $condition = $request->input('condition', 'under');
                if (!in_array($condition, ['under', 'over'], true)) throw new \DomainException('Invalid dice condition.');
                $target = $this->number($request->input('target'), $condition === 'under' ? 1 : 1.99, $condition === 'under' ? 98 : 98.99);
                if (abs($target * 100 - round($target * 100)) > 0.00001) throw new \DomainException('Target requires two decimal places.');
                $target = (int) round($target * 100);
                $chance = ($condition === 'under' ? $target : 9999 - $target) / 100;
                $roll = $sample(10000);
                $won = $condition === 'under' ? $roll < $target : $roll > $target;
                $multiplier = floor((100 - $edge) / $chance * 10000) / 10000;
                $win = $won ? $this->payout($wager, $multiplier) : 0;
                $result += ['roll' => number_format($roll / 100, 2, '.', ''), 'is_win' => $won, 'win' => $won,
                    'target' => $target / 100, 'condition' => $condition, 'multiplier' => $multiplier];
                $d['parameters'] = ['target' => $target / 100, 'condition' => $condition];
                break;
            case 'CedarWheel':
                $count = $this->integer($request->input('segments', 10), 10, 50);
                $risk = $this->risk($request);
                $tables = CedarMath::wheel();
                if (!isset($tables[$count])) throw new \DomainException('Invalid segment count.');
                $index = $sample($count);
                $table = $tables[$count][$risk];
                $multiplier = $table[$index];
                $edge = 100 * (1 - array_sum($table) / $count);
                $win = $this->payout($wager, $multiplier);
                $result += ['winning_index' => $index, 'multiplier' => $multiplier, 'is_win' => $win > $wager,
                    'segments_count' => $count, 'risk' => $risk, 'segments_table' => $table];
                $d['parameters'] = ['segments' => $count, 'risk' => $risk];
                break;
            case 'CedarPlinko':
                $rows = $this->integer($request->input('rows', 16), 8, 16);
                $risk = $this->risk($request);
                $directions = [];
                for ($i = 0; $i < $rows; $i++) $directions[] = $sample(2, $i);
                $index = array_sum($directions);
                $multiplier = CedarTables::PLINKO[$rows][$risk][$index];
                $edge = 100 * (1 - CedarMath::plinkoRtp($rows, $risk));
                $win = $this->payout($wager, $multiplier);
                $result += ['rows' => $rows, 'risk' => $risk, 'directions' => $directions, 'slot_index' => $index, 'multiplier' => $multiplier];
                $d['parameters'] = ['rows' => $rows, 'risk' => $risk];
                break;
            case 'CedarMines':
                $count = $this->integer($request->input('mines', 3), 1, 24);
                $d += ['mines_count' => $count, 'mine_positions' => CedarMath::mines($d['server_seed'], $client, $d['nonce'], $count), 'revealed' => []];
                $d['parameters'] = ['mines' => $count];
                $next = CedarMath::minesMultiplier($count, 1, $edge);
                $result += ['mines_count' => $count, 'next_multiplier' => $next, 'next_win' => $this->payout($wager, $next), 'diamonds_left' => 25 - $count];
                break;
            case 'CedarCrash':
                $auto = $this->number($request->input('auto_cashout', 0), 0, $this->rules['max_multiplier']);
                if ($auto > 0 && $auto < 1.01) throw new \DomainException('Auto cashout must be at least 1.01.');
                $d += ['crash_multiplier' => CedarMath::crash($d['server_seed'], $client, $d['nonce'], $edge, $this->rules['max_multiplier']),
                    'start_time' => microtime(true), 'auto_cashout' => $auto];
                $d['parameters'] = ['auto_cashout' => $auto];
                $result += ['elapsed' => 0, 'multiplier' => 1.0];
                break;
        }
        $round = ['id' => (string) Str::uuid(), 'user_id' => $this->user->id, 'game' => $this->game,
            'request_id' => $id, 'status' => 'active', 'wager' => $wager, 'win' => 0, 'data' => $d];
        $this->user->decrement('balance', $wager);
        // One award per accepted wager; the audit row below does not emit StatGame's legacy observer.
        AffiliateService::recordWagerCommission($this->user, $wager, strtolower(substr($this->game, 5)), true);
        VipService::recordWagerXpAndRakeback($this->user, $wager, $edge);
        $this->user->refresh();
        $this->audit($wager, 0);
        $this->state = ['server_seed' => bin2hex(random_bytes(32)), 'client_seed' => $client, 'nonce' => $d['nonce'] + 1, 'active' => $round['id']];
        $result += ['bet_id' => $round['id'], 'wager' => $wager, 'server_seed_hash' => hash('sha256', $d['server_seed']),
            'client_seed' => $client, 'nonce' => $d['nonce'], 'next_server_seed_hash' => hash('sha256', $this->state['server_seed']),
            'balance' => $this->balance(), 'new_balance' => $this->balance(), 'max_payout' => $d['rules']['max_payout']];
        $round['data']['bet_result'] = $result;
        $this->saveRound($round, true);
        if (in_array($this->game, ['CedarMines', 'CedarCrash', 'RoyalSteps'])) return $result;
        return $this->settle($round, $win, $result);
    }

    private function number($value, float $min, float $max): float
    {
        if (!is_scalar($value) || is_bool($value) || !is_numeric($value) || !is_finite((float) $value) || (float) $value < $min || (float) $value > $max) {
            throw new \DomainException("Enter a number between $min and $max.");
        }
        return (float) $value;
    }
    private function roundId(Request $request): string
    {
        $id = $request->input('bet_id', '');
        if (!is_string($id) || strlen($id) > 64) throw new \DomainException('Invalid round ID.');
        return $id;
    }
    private function integer($value, int $min, int $max): int
    {
        $n = $this->number($value, $min, $max);
        if (floor($n) !== $n) throw new \DomainException('A whole number is required.');
        return (int) $n;
    }
    private function risk(Request $request): string
    {
        $risk = $request->input('risk', 'medium');
        if (!in_array($risk, ['low', 'medium', 'high'], true)) throw new \DomainException('Invalid risk.');
        return $risk;
    }
    private function payout(float $wager, float $multiplier, ?array $rules = null): float
    {
        return min(($rules ?? $this->rules)['max_payout'], floor(($wager * $multiplier + 1e-8) * 100) / 100);
    }
    private function active(): ?array { return $this->state['active'] ? $this->findRound($this->state['active']) : null; }
    private function findRound(string $id): ?array
    {
        $row = DB::table('cedar_rounds')->where('id', $id)->where('user_id', $this->user->id)->where('game', $this->game)->first();
        return $row ? $this->decode($row) : null;
    }
    private function decode($row): array
    {
        $round = (array) $row;
        $round['data'] = json_decode($round['data'], true, 512, JSON_THROW_ON_ERROR);
        return $round;
    }
    private function saveRound(array $round, bool $insert = false): void
    {
        $round['data'] = json_encode($round['data'], JSON_THROW_ON_ERROR);
        $round['updated_at'] = now();
        if ($insert) { $round['created_at'] = now(); DB::table('cedar_rounds')->insert($round); }
        else DB::table('cedar_rounds')->where('id', $round['id'])->update($round);
    }
    private function audit(float $bet, float $win): void
    {
        $stat = new StatGame(['user_id' => $this->user->id, 'balance' => $this->user->balance, 'bet' => $bet,
            'win' => $win, 'game' => $this->game, 'in_game' => 1, 'shop_id' => $this->user->shop_id ?: 1, 'date_time' => now()]);
        $stat->saveQuietly();
    }
    private function settle(array $round, float $win, array $result): array
    {
        if ($win > 0) $this->user->increment('balance', $win);
        $this->audit(0, $win);
        $this->state['active'] = null;
        $round['status'] = 'settled';
        $round['win'] = $win;
        $result = array_merge($result, [
            'bet_id' => $round['id'], 'wager' => (float) $round['wager'], 'win_amount' => number_format($win, 2, '.', ''),
            'net_profit' => round($win - $round['wager'], 2), 'profit' => round($win - $round['wager'], 2),
            'new_balance' => $this->balance(), 'balance' => $this->balance(),
            'server_seed' => $round['data']['server_seed'], 'revealed_server_seed' => $round['data']['server_seed'],
            'server_seed_hash' => hash('sha256', $round['data']['server_seed']),
            'next_server_seed_hash' => hash('sha256', $this->state['server_seed']),
            'client_seed' => $round['data']['client_seed'], 'nonce' => $round['data']['nonce'],
            'math_version' => CedarMath::VERSION,
        ]);
        $round['data']['result'] = $result;
        $result['proof'] = $this->proof($round);
        $round['data']['result'] = $result;
        $this->saveRound($round);
        return $result;
    }
    private function proof(array $round): array
    {
        $d = $round['data'];
        return ['bet_id' => $round['id'], 'game' => $this->game, 'math_version' => $d['math_version'],
            'server_seed' => $d['server_seed'], 'server_seed_hash' => hash('sha256', $d['server_seed']),
            'client_seed' => $d['client_seed'], 'nonce' => $d['nonce'], 'parameters' => $d['parameters'],
            'rules' => $d['rules'], 'wager' => (float) $round['wager'], 'win' => (float) $round['win'],
            'outcome' => array_intersect_key($d['result'] ?? [], array_flip(['roll', 'winning_index', 'slot_index', 'directions', 'multiplier', 'mine_positions', 'crash_multiplier', 'trap_step', 'step']))];
    }
    private function stepsView(array $round): array
    {
        $d = $round['data'];
        $step = $d['step'];
        $mult = $step ? $d['parameters']['ladder'][$step - 1] : 1;
        return ['status' => 'climbing', 'bet_id' => $round['id'], 'step' => $step,
            'wager' => (float) $round['wager'], 'multiplier' => $mult,
            'current_win' => $step ? $this->payout($round['wager'], $mult, $d['rules']) : 0,
            'balance' => $this->balance(), 'server_seed_hash' => hash('sha256', $d['server_seed'])];
    }

    private function stepsAction(array $round, Request $request, string $action): array
    {
        $d = $round['data'];
        if ($action === 'step') {
            $step = $this->integer($request->input('step'), 1, 10);
            // Repeated delivery of an acknowledged step never climbs again.
            if ($step <= $d['step']) return $this->stepsView($round);
            if ($step !== $d['step'] + 1) throw new \DomainException('Climb one step at a time. Reload to recover your round.');
            if ($step >= $d['trap_step']) return $this->settle($round, 0,
                ['status' => 'bust', 'step' => $step, 'trap_step' => $d['trap_step'], 'multiplier' => 0]);
            $round['data']['step'] = $step;
        }
        $view = $this->stepsView($round);
        if ($action === 'cashout' || $round['data']['step'] === 10) {
            if (!$round['data']['step']) throw new \DomainException('Climb a safe step before collecting.');
            return $this->settle($round, $view['current_win'], array_merge($view,
                ['status' => 'cashed_out', 'trap_step' => $d['trap_step']]));
        }
        $this->saveRound($round);
        return $view;
    }

    private function minesView(array $round): array
    {
        $d = $round['data'];
        $n = count($d['revealed']);
        $mult = CedarMath::minesMultiplier($d['mines_count'], $n, $d['rules']['house_edge']);
        $next = CedarMath::minesMultiplier($d['mines_count'], min($n + 1, 25 - $d['mines_count']), $d['rules']['house_edge']);
        return ['bet_id' => $round['id'], 'wager' => (float) $round['wager'], 'mines_count' => $d['mines_count'], 'revealed' => $d['revealed'],
            'house_edge' => $d['rules']['house_edge'], 'max_payout' => $d['rules']['max_payout'],
            'revealed_count' => $n, 'current_multiplier' => $mult, 'multiplier' => $mult,
            'current_win' => $this->payout($round['wager'], $mult, $d['rules']), 'next_multiplier' => $next,
            'next_win' => $this->payout($round['wager'], $next, $d['rules']), 'diamonds_left' => 25 - $d['mines_count'] - $n,
            'server_seed_hash' => hash('sha256', $d['server_seed']), 'client_seed' => $d['client_seed'], 'nonce' => $d['nonce']];
    }
    private function minesAction(array $round, Request $request, string $action): array
    {
        $d = $round['data'];
        if ($action === 'cashout') {
            if (!$d['revealed']) throw new \DomainException('Reveal a safe tile before cashing out.');
            $view = $this->minesView($round);
            return $this->settle($round, $view['current_win'], ['status' => 'cashed_out', 'multiplier' => $view['multiplier'], 'mine_positions' => $d['mine_positions']]);
        }
        $tile = $this->integer($request->input('tile'), 0, 24);
        if (in_array($tile, $d['mine_positions'], true)) {
            return $this->settle($round, 0, ['status' => 'bust', 'tile' => $tile, 'mine_positions' => $d['mine_positions']]);
        }
        if (!in_array($tile, $d['revealed'], true)) $round['data']['revealed'][] = $tile;
        $view = $this->minesView($round);
        if ($view['diamonds_left'] === 0) {
            return $this->settle($round, $view['current_win'], ['status' => 'cleared', 'tile' => $tile, 'multiplier' => $view['multiplier'], 'mine_positions' => $d['mine_positions']]);
        }
        $this->saveRound($round);
        return array_merge($view, ['status' => 'diamond', 'tile' => $tile]);
    }
    private function crashAction(array $round, string $action): array
    {
        $d = $round['data'];
        $elapsed = max(0, microtime(true) - $d['start_time']);
        $crash = (float) $d['crash_multiplier'];
        $auto = $d['auto_cashout'];
        // Auto-cashout is committed with the wager and remains valid through disconnects.
        if ($auto > 0 && $auto < $crash && $elapsed >= CedarMath::flightSeconds($auto)) {
            return $this->settle($round, $this->payout($round['wager'], $auto, $d['rules']), ['status' => 'success', 'multiplier' => $auto, 'crash_multiplier' => $crash]);
        }
        if ($crash === (float) $d['rules']['max_multiplier'] && $elapsed >= CedarMath::flightSeconds($crash)) {
            return $this->settle($round, $this->payout($round['wager'], $crash, $d['rules']), ['status' => 'success', 'multiplier' => $crash, 'crash_multiplier' => $crash]);
        }
        if ($elapsed >= CedarMath::flightSeconds($crash)) {
            return $this->settle($round, 0, ['status' => 'crashed', 'multiplier' => $crash, 'crash_multiplier' => $crash]);
        }
        $mult = CedarMath::flight($elapsed);
        if ($action === 'cashout') {
            return $this->settle($round, $this->payout($round['wager'], $mult, $d['rules']), ['status' => 'success', 'multiplier' => $mult, 'crash_multiplier' => $crash]);
        }
        return ['status' => 'flying', 'bet_id' => $round['id'], 'elapsed' => $elapsed, 'multiplier' => $mult, 'wager' => (float) $round['wager']];
    }
}
