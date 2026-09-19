<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/fixture-bootstrap.php';
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use VanguardLTE\Games\CedarMath;
use VanguardLTE\Services\CedarGameService;
use VanguardLTE\Services\VipService;
use VanguardLTE\User;

$checks = 0;
function check(bool $condition, string $name): void {
    global $checks;
    if (!$condition) throw new RuntimeException('FAIL: ' . $name);
    $checks++;
}
function callGame(string $game, array $payload): array {
    return (new CedarGameService())->handle(new Request($payload), $game);
}
function bet(string $game, array $payload): array {
    $init = callGame($game, ['action' => 'init']);
    return callGame($game, $payload + ['request_id' => bin2hex(random_bytes(16)), 'server_seed_hash' => $init['server_seed_hash'], 'client_seed' => 'regression']);
}
function ageRound(string $id, float $seconds, ?float $point = null): void {
    $row = DB::table('cedar_rounds')->where('id', $id)->first();
    $data = json_decode($row->data, true);
    $data['start_time'] = microtime(true) - $seconds;
    if ($point !== null) $data['crash_multiplier'] = $point;
    DB::table('cedar_rounds')->where('id', $id)->update(['data' => json_encode($data)]);
}

$auth->userId = null;
check(callGame('CedarDice', ['action' => 'init'])['status'] === 'error', 'guest rejected');
$auth->userId = 1;
foreach (CedarMath::wheel() as $n => $risks) foreach ($risks as $risk => $table) {
    check(count($table) === $n && array_sum($table) / $n <= 0.95000001 && array_sum($table) / $n >= 0.9499, "Wheel $n $risk RTP");
}
for ($rows = 8; $rows <= 16; $rows++) foreach (['low', 'medium', 'high'] as $risk) check(CedarMath::plinkoRtp($rows, $risk) <= 0.95000001, "Plinko $rows $risk RTP");
$initial = callGame('CedarDice', ['action' => 'init']);
$payload = ['action' => 'roll', 'wager' => 1000, 'target' => 98.99, 'condition' => 'over', 'request_id' => str_repeat('a', 32), 'server_seed_hash' => $initial['server_seed_hash']];
$a = callGame('CedarDice', $payload);
check($a['status'] === 'success' && $a['multiplier'] === 95.0, 'Dice exact over probability capped at 95% RTP');
$balance = User::find(1)->balance;
$b = callGame('CedarDice', $payload);
check($a == $b && User::find(1)->balance === $balance, 'request replay pays once');
check(User::find(1)->vip_xp === 1000, 'XP awarded once');
check((float) User::find(2)->unclaimed_commissions === 5.0, 'commission awarded once');
check(DB::table('stat_game')->sum('bet') == 1000, 'wager audited once');
check(hash('sha256', $a['server_seed']) === $initial['server_seed_hash'], 'revealed seed matches prior commitment');
check(callGame('CedarDice', $payload + ['unused' => true]) == $a, 'same request returns immutable response');
foreach ([-1, 0, 'NaN', '1e999', [], 10.001] as $bad) check(bet('CedarDice', ['action' => 'roll', 'wager' => $bad, 'target' => 50])['status'] === 'error', 'invalid wager rejected');
$oldBalance = User::find(1)->balance;
$settings->values['enable_cedar_originals'] = '0';
check(bet('CedarWheel', ['action' => 'spin', 'wager' => 10])['status'] === 'error', 'module kill switch');
$settings->values['enable_cedar_originals'] = '1';
check(User::find(1)->balance === $oldBalance, 'invalid requests never charge');
foreach (['CedarWheel' => ['action' => 'spin', 'segments' => 20], 'CedarPlinko' => ['action' => 'drop', 'rows' => 16]] as $game => $params) {
    $r = bet($game, $params + ['wager' => 10]);
    check($r['status'] === 'success' && isset($r['proof']), "$game settles and reveals proof");
}
$mine = bet('CedarMines', ['action' => 'bet', 'wager' => 100, 'mines' => 3]);
check(!isset($mine['server_seed']) && !isset($mine['mine_positions']), 'Mines secrets withheld');
check(bet('CedarMines', ['action' => 'bet', 'wager' => 100])['status'] === 'error', 'second active Mines bet rejected');
$row = DB::table('cedar_rounds')->where('id', $mine['bet_id'])->first(); $d = json_decode($row->data, true);
$safe = array_values(array_diff(range(0, 24), $d['mine_positions']))[0];
$reveal = callGame('CedarMines', ['action' => 'reveal', 'tile' => $safe, 'bet_id' => $mine['bet_id']]);
check($reveal['status'] === 'diamond', 'safe reveal');
check(callGame('CedarMines', ['action' => 'reveal', 'tile' => $safe, 'bet_id' => $mine['bet_id']])['revealed_count'] === 1, 'reveal retry does not advance multiplier');
$settings->values['enable_cedar_originals'] = '0';
$cash = callGame('CedarMines', ['action' => 'cashout', 'bet_id' => $mine['bet_id']]);
check($cash['status'] === 'cashed_out', 'disabled game allows existing cashout');
$balance = User::find(1)->balance;
check(callGame('CedarMines', ['action' => 'cashout', 'bet_id' => $mine['bet_id']]) == $cash && User::find(1)->balance === $balance, 'Mines cashout replay');
$settings->values['enable_cedar_originals'] = '1';
$crash = bet('CedarCrash', ['action' => 'bet', 'wager' => 100, 'auto_cashout' => 0]);
check(!isset($crash['crash_multiplier']) && !isset($crash['server_seed']), 'Crash outcome withheld');
ageRound($crash['bet_id'], 0, 10);
$cash = callGame('CedarCrash', ['action' => 'cashout', 'bet_id' => $crash['bet_id'], 'multiplier' => 999]);
check($cash['status'] === 'success' && $cash['multiplier'] < 1.1, 'Crash ignores forged cashout multiplier');
$balance = User::find(1)->balance;
callGame('CedarCrash', ['action' => 'cashout', 'bet_id' => $crash['bet_id']]);
check(User::find(1)->balance === $balance, 'Crash cashout replay');
$crash = bet('CedarCrash', ['action' => 'bet', 'wager' => 100, 'auto_cashout' => 2]);
ageRound($crash['bet_id'], 1000, 3);
$cash = callGame('CedarCrash', ['action' => 'status', 'bet_id' => $crash['bet_id']]);
check($cash['status'] === 'success' && (float) $cash['win_amount'] === 200.0, 'auto cashout survives disconnect and deadline');
$crash = bet('CedarCrash', ['action' => 'bet', 'wager' => 100, 'auto_cashout' => 0]);
ageRound($crash['bet_id'], 1000, 2);
check(callGame('CedarCrash', ['action' => 'cashout', 'bet_id' => $crash['bet_id'], 'multiplier' => 1])['status'] === 'crashed', 'late manual cashout loses');
$stale = User::find(1);
VipService::claimRakeback(User::find(1));
VipService::recordWagerXpAndRakeback($stale, 100, 1);
check(abs((float) User::find(1)->unclaimed_rakeback - 0.05) < 0.00001, 'stale user does not resurrect claimed rakeback');
$auth->userId = 2;
check(callGame('CedarCrash', ['action' => 'cashout', 'bet_id' => $crash['bet_id']])['status'] === 'error', 'round ownership enforced');
$auth->userId = 1;
check(callGame('CedarMines', ['action' => 'reveal', 'bet_id' => []])['status'] === 'error', 'malformed round ID rejected');

// Scheduler shares the settlement path and can finish an offline player's committed auto-cashout.
$crash = bet('CedarCrash', ['action' => 'bet', 'wager' => 100, 'auto_cashout' => 2]);
ageRound($crash['bet_id'], 1000, 3);
$balance = (float) User::find(1)->balance;
(new CedarGameService())->settleCrashFor(1);
(new CedarGameService())->settleCrashFor(1);
check(abs((float) User::find(1)->balance - $balance - 200) < 0.0001, 'scheduler settles offline auto-cashout once');
$settings->values['cedar_crash_max_multiplier'] = 2;
$crash = bet('CedarCrash', ['action' => 'bet', 'wager' => 100, 'auto_cashout' => 0]);
ageRound($crash['bet_id'], 1000, 2);
$cash = callGame('CedarCrash', ['action' => 'status', 'bet_id' => $crash['bet_id']]);
check($cash['status'] === 'success' && (float) $cash['win_amount'] === 200.0, 'surviving Crash cap pays automatically');
unset($settings->values['cedar_crash_max_multiplier']);

// Accounting failure must roll back debit, XP, commissions, audit, and round state together.
$snapshot = User::find(1)->getAttributes();
$commissions = DB::table('affiliate_commissions')->count();
$rounds = DB::table('cedar_rounds')->count();
$db->schema()->rename('stat_game', 'stat_game_unavailable');
$failed = false;
try { bet('CedarDice', ['action' => 'roll', 'wager' => 100, 'target' => 50]); }
catch (Illuminate\Database\QueryException $e) { $failed = true; }
finally { $db->schema()->rename('stat_game_unavailable', 'stat_game'); }
check($failed, 'audit failure fails the wager');
check(User::find(1)->getAttributes() === $snapshot, 'audit failure rolls wallet and rewards back');
check(DB::table('affiliate_commissions')->count() === $commissions && DB::table('cedar_rounds')->count() === $rounds, 'audit failure leaves no partial records');

// Existing legacy rounds get a recorded refund exactly once when upgrading that player's state.
DB::table('users')->insert(['id' => 3, 'balance' => 1000, 'parent_id' => 0]);
$auth->userId = 3;
$container->make('cache')->values['cedar_mines_active_3'] = ['wager' => 100];
callGame('CedarMines', ['action' => 'init']);
callGame('CedarMines', ['action' => 'init']);
check((float) User::find(3)->balance === 1100.0, 'legacy unfinished round refunded once');
check(DB::table('cedar_rounds')->where('user_id', 3)->where('status', 'void')->count() === 1, 'legacy refund has durable audit');
$auth->userId = 1;
// Royal Steps: exercise real wins/losses from committed seeds, without altering outcomes.
function stepsRound(int $trap): array {
    $init = callGame('RoyalSteps', ['action' => 'init']);
    for ($i = 0; ; $i++) {
        $seed = hash('sha256', 'royal-regression-' . $i);
        if (CedarMath::stepsTrap($seed, 'royal-test', 1, 5) === $trap) break;
    }
    DB::table('cedar_states')->where('user_id', 1)->where('game', 'RoyalSteps')->update(['state' => json_encode([
        'server_seed' => $seed, 'client_seed' => 'royal-test', 'nonce' => 1, 'active' => null])]);
    return bet('RoyalSteps', ['action' => 'bet', 'wager' => 100, 'client_seed' => 'royal-test']);
}
$royal = stepsRound(1);
check(!isset($royal['trap_step']) && !isset($royal['server_seed']), 'Royal Steps conceals trap and seed');
check(callGame('RoyalSteps', ['action' => 'cashout', 'bet_id' => $royal['bet_id'], 'wager' => 999999, 'multiplier' => 100])['status'] === 'error', 'cannot collect before climbing');
$lost = callGame('RoyalSteps', ['action' => 'step', 'step' => 1, 'bet_id' => $royal['bet_id']]);
check($lost['status'] === 'bust' && (float) $lost['win_amount'] === 0.0, 'Royal Steps actually loses at trap');
$balance = User::find(1)->balance;
check(callGame('RoyalSteps', ['action' => 'cashout', 'bet_id' => $royal['bet_id']]) == $lost && User::find(1)->balance === $balance, 'losing round cannot be cashed out');
$royal = stepsRound(3);
check(callGame('RoyalSteps', ['action' => 'step', 'step' => 10, 'bet_id' => $royal['bet_id']])['status'] === 'error', 'cannot skip steps');
$safe = callGame('RoyalSteps', ['action' => 'step', 'step' => 1, 'bet_id' => $royal['bet_id']]);
check($safe['status'] === 'climbing' && $safe['step'] === 1, 'safe step accepted');
check(callGame('RoyalSteps', ['action' => 'step', 'step' => 1, 'bet_id' => $royal['bet_id']])['step'] === 1, 'step replay does not advance twice');
$recover = callGame('RoyalSteps', ['action' => 'init']);
check($recover['active_game']['step'] === 1 && !isset($recover['active_game']['trap_step']), 'reload restores safe progress without exposing trap');
$cash = callGame('RoyalSteps', ['action' => 'cashout', 'bet_id' => $royal['bet_id'], 'wager' => 999999, 'multiplier' => 100]);
check($cash['status'] === 'cashed_out' && (float) $cash['win_amount'] === 120.0, 'cashout ignores forged wager and multiplier');
$balance = User::find(1)->balance;
check(callGame('RoyalSteps', ['action' => 'cashout', 'bet_id' => $royal['bet_id']]) == $cash && User::find(1)->balance === $balance, 'Royal Steps cashout credits once');
$royal = stepsRound(11);
for ($i = 1; $i <= 10; $i++) $top = callGame('RoyalSteps', ['action' => 'step', 'step' => $i, 'bet_id' => $royal['bet_id']]);
check($top['status'] === 'cashed_out' && (float) $top['win_amount'] === 10000.0, 'surviving tower automatically pays top prize');
check(CedarMath::stepsTrap($top['proof']['server_seed'], $top['proof']['client_seed'], $top['proof']['nonce'], 5) === $top['proof']['outcome']['trap_step'], 'Royal Steps proof reproduces outcome');
foreach (CedarMath::STEPS as $mult) {
    $rtp = floor(9500 / $mult) / 10000 * $mult;
    check($rtp <= 0.95000001 && $rtp >= 0.947, 'Royal Steps stopping-point RTP capped at 95%');
}

// Additional Cedar Originals: instant games settle atomically with proofs.
$limbo = bet('CedarLimbo', ['action' => 'play', 'wager' => 100, 'target' => 2]);
check($limbo['status'] === 'success' && isset($limbo['proof']['outcome']['draw']), 'Limbo settles with reproducible draw');
$coin = bet('CedarCoinFlip', ['action' => 'flip', 'wager' => 100, 'choice' => 'heads']);
check($coin['status'] === 'success' && in_array($coin['outcome'], ['heads', 'tails'], true) && isset($coin['proof']), 'Coin Flip settles with proof');
$keno = bet('CedarKeno', ['action' => 'play', 'wager' => 100, 'picks' => [1, 2, 3, 4, 5]]);
check($keno['status'] === 'success' && count($keno['drawn']) === 10 && isset($keno['proof']), 'Keno draws ten unique numbers with proof');
check(count(array_unique($keno['drawn'])) === 10, 'Keno draw has no duplicates');
$table = CedarMath::kenoTable(5); $kenoRtp = 0;
for ($hits = 0; $hits <= 5; $hits++) $kenoRtp += (function($n,$k){if($k<0||$k>$n)return 0;$v=1;$k=min($k,$n-$k);for($i=1;$i<=$k;$i++)$v*=($n-$k+$i)/$i;return $v;})(5,$hits)
    * (function($n,$k){if($k<0||$k>$n)return 0;$v=1;$k=min($k,$n-$k);for($i=1;$i<=$k;$i++)$v*=($n-$k+$i)/$i;return $v;})(35,10-$hits)
    / (function($n,$k){$v=1;$k=min($k,$n-$k);for($i=1;$i<=$k;$i++)$v*=($n-$k+$i)/$i;return $v;})(40,10) * $table[$hits];
check($kenoRtp <= 0.95000001 && $kenoRtp >= 0.9499, 'Keno RTP capped at 95%');

// Cedar Cards: committed card draws, recoverable hands and conservative payouts.
$hiInit = callGame('CedarHiLo', ['action' => 'init']);
$hiChoice = $hiInit['preview_card']['rank'] === 12 ? 'lower' : 'higher';
$hi = callGame('CedarHiLo', ['action' => 'bet', 'wager' => 100, 'choice' => $hiChoice,
    'request_id' => bin2hex(random_bytes(16)), 'server_seed_hash' => $hiInit['server_seed_hash'], 'client_seed' => 'cards']);
check($hi['status'] === 'success' && isset($hi['proof']['outcome']['up_card'], $hi['proof']['outcome']['draw_card']), 'Hi-Lo settles with both committed cards');
$rank = $hi['up_card']['rank']; $winning = $hiChoice === 'higher' ? 12 - $rank : $rank;
check(abs(($winning / 13) * $hi['multiplier'] - 0.95) < 0.0001, 'Hi-Lo chosen direction RTP is 95% or less');
$blackjack = bet('CedarBlackjack', ['action' => 'bet', 'wager' => 100]);
check($blackjack['status'] === 'active' && count($blackjack['player_cards']) === 2 && count($blackjack['dealer_cards']) === 1, 'Blackjack conceals dealer hole card');
$recover = callGame('CedarBlackjack', ['action' => 'init']);
check($recover['active_game']['bet_id'] === $blackjack['bet_id'], 'Blackjack hand recovers after reload');
$blackjackEnd = callGame('CedarBlackjack', ['action' => 'stand', 'bet_id' => $blackjack['bet_id']]);
check(in_array($blackjackEnd['status'], ['won','lost','push'], true) && isset($blackjackEnd['proof']['outcome']['deck']), 'Blackjack settles with reproducible deck proof');
check((float) $blackjackEnd['multiplier'] <= 1.9, 'Blackjack payout remains below even-money 95% cap');

foreach (['CedarTower' => [3,7], 'CedarGoal' => [3,5], 'CedarTreasure' => [4,8]] as $pathGame => [$choices,$levels]) {
    $round = bet($pathGame, ['action' => 'bet', 'wager' => 100]);
    $row = DB::table('cedar_rounds')->where('id', $round['bet_id'])->first(); $pathData = json_decode($row->data, true);
    $safeChoice = ($pathData['hazards'][0] + 1) % $choices;
    $safeResult = callGame($pathGame, ['action' => 'choose', 'choice' => $safeChoice, 'bet_id' => $round['bet_id']]);
    check($safeResult['status'] === 'safe' && $safeResult['level'] === 1, "$pathGame accepts safe choice");
    $recover = callGame($pathGame, ['action' => 'init']);
    check($recover['active_game']['level'] === 1 && !isset($recover['active_game']['hazards']), "$pathGame recovers without revealing hazards");
    $cashout = callGame($pathGame, ['action' => 'cashout', 'bet_id' => $round['bet_id']]);
    check($cashout['status'] === 'cashed_out' && isset($cashout['proof']['outcome']['hazards']), "$pathGame cashes out with proof");
    foreach (CedarMath::pathLadder($choices, $levels, 5) as $i => $mult) {
        check((($choices - 1) / $choices) ** ($i + 1) * $mult <= 0.95000001, "$pathGame level RTP capped");
    }
}

echo "PASS: $checks Cedar regression checks (isolated " . (defined('CEDAR_TEST_CONNECTION') ? 'MySQL' : 'SQLite') . ").\n";
