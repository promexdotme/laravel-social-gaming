<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Exercise the real local schema in a transaction that is always rolled back.
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use VanguardLTE\Services\CedarGameService;
if (!in_array(config('database.connections.mysql.host'), ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Local database required.');
foreach (DB::select("SHOW TABLE STATUS WHERE Name IN ('w_users','w_stat_game','w_cedar_rounds','w_cedar_states')") as $table) {
    if ($table->Engine !== 'InnoDB') throw new RuntimeException('Transactional tables required.');
}
DB::beginTransaction();
try {
    $id = DB::table('users')->insertGetId(['username' => 'cedar_test_' . bin2hex(random_bytes(5)),
        'email' => bin2hex(random_bytes(8)) . '@example.invalid', 'password' => password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT),
        'balance' => 100000, 'shop_id' => 1, 'status' => 'Active', 'is_demo_agent' => 0, 'parent_id' => 0]);
    Auth::setUser(VanguardLTE\User::findOrFail($id));
    $games = ['CedarDice' => ['roll', []], 'CedarWheel' => ['spin', []], 'CedarPlinko' => ['drop', []],
        'CedarMines' => ['bet', []], 'CedarCrash' => ['bet', []], 'RoyalSteps' => ['bet', []],
        'CedarLimbo' => ['play', ['target' => 2]], 'CedarTower' => ['bet', []],
        'CedarKeno' => ['play', ['picks' => [1,2,3,4,5]]], 'CedarCoinFlip' => ['flip', ['choice' => 'heads']],
        'CedarGoal' => ['bet', []], 'CedarTreasure' => ['bet', []],
        'CedarHiLo' => ['bet', ['choice' => 'higher']], 'CedarBlackjack' => ['bet', []]];
    foreach ($games as $game => [$action, $extra]) {
        $service = new CedarGameService();
        $init = $service->handle(new Illuminate\Http\Request(['action' => 'init']), $game);
        if ($game === 'CedarHiLo') $extra['choice'] = $init['preview_card']['rank'] === 12 ? 'lower' : 'higher';
        $result = $service->handle(new Illuminate\Http\Request($extra + ['action' => $action, 'wager' => 10, 'target' => 50,
            'server_seed_hash' => $init['server_seed_hash'], 'request_id' => bin2hex(random_bytes(16))]), $game);
        if (!in_array($result['status'], $game === 'CedarBlackjack' ? ['active'] : ['success'], true)) throw new RuntimeException($game . ': ' . ($result['message'] ?? 'failed'));
        echo $game . ': real schema accepted round.' . PHP_EOL;
    }
} finally {
    DB::rollBack();
}
if (DB::table('users')->where('id', $id)->exists() || DB::table('cedar_rounds')->where('user_id', $id)->exists()) throw new RuntimeException('Fixture rollback failed.');
echo 'PASS: synthetic account, balances and rounds rolled back.' . PHP_EOL;
