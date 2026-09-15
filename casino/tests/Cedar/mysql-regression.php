<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Creates and drops ONLY a uniquely named local fixture database. Never uses player tables.
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$connection = config('database.connections.mysql');
if (!in_array($connection['host'], ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('MySQL regression tests require a local database host.');
}
$database = $argv[1] ?? ('cedar_test_' . bin2hex(random_bytes(8)));
if (!preg_match('/^cedar_test_[a-f0-9]{16}$/D', $database)) throw new RuntimeException('Invalid fixture database name.');
$pdo = new PDO('mysql:host=' . $connection['host'] . ';port=' . ($connection['port'] ?? 3306), $connection['username'], $connection['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$connection['database'] = $database;
$connection['url'] = null;
define('CEDAR_TEST_CONNECTION', $connection);

if (isset($argv[1])) {
    require __DIR__ . '/fixture-bootstrap.php';
    $payload = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    $result = (new VanguardLTE\Services\CedarGameService())->handle(new Illuminate\Http\Request($payload['body']), $payload['game']);
    echo json_encode($result, JSON_THROW_ON_ERROR);
    exit;
}

$pdo->exec("CREATE DATABASE `$database`");
try {
    require __DIR__ . '/cedar-regression.php';
    $auth->userId = 1;
    function concurrentCalls(string $database, array $payloads): array {
        $workers = [];
        foreach ($payloads as $payload) {
            $process = proc_open([PHP_BINARY, __FILE__, $database], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) throw new RuntimeException('Unable to start concurrency worker.');
            fwrite($pipes[0], json_encode($payload)); fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        $results = [];
        foreach ($workers as [$process, $pipes]) {
            $out = stream_get_contents($pipes[1]); $err = stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            if (proc_close($process) !== 0) throw new RuntimeException('Concurrency worker failed: ' . $err);
            $results[] = json_decode($out, true, 512, JSON_THROW_ON_ERROR);
        }
        return $results;
    }
    $init = callGame('CedarDice', ['action' => 'init']);
    $request = ['game' => 'CedarDice', 'body' => ['action' => 'roll', 'wager' => 10, 'target' => 50, 'request_id' => bin2hex(random_bytes(16)), 'server_seed_hash' => $init['server_seed_hash']]];
    $before = Illuminate\Support\Facades\DB::table('cedar_rounds')->count();
    $results = concurrentCalls($database, [$request, $request, $request, $request]);
    check(count(array_unique(array_column($results, 'bet_id'))) === 1, 'MySQL concurrent duplicate bets return one round');
    check(Illuminate\Support\Facades\DB::table('cedar_rounds')->count() === $before + 1, 'MySQL concurrent duplicate bets write once');
    $round = bet('CedarMines', ['action' => 'bet', 'wager' => 100, 'mines' => 3]);
    $row = Illuminate\Support\Facades\DB::table('cedar_rounds')->where('id', $round['bet_id'])->first();
    $data = json_decode($row->data, true);
    $safe = array_values(array_diff(range(0, 24), $data['mine_positions']))[0];
    callGame('CedarMines', ['action' => 'reveal', 'tile' => $safe, 'bet_id' => $round['bet_id']]);
    $balance = (float) VanguardLTE\User::find(1)->balance;
    $cashout = ['game' => 'CedarMines', 'body' => ['action' => 'cashout', 'bet_id' => $round['bet_id']]];
    $results = concurrentCalls($database, [$cashout, $cashout, $cashout, $cashout]);
    check(abs((float) VanguardLTE\User::find(1)->balance - $balance - (float) $results[0]['win_amount']) < 0.0001, 'MySQL concurrent cashouts credit once');
    $payloads = [];
    foreach (['CedarMines', 'CedarCrash'] as $game) {
        $init = callGame($game, ['action' => 'init']);
        $payloads[] = ['game' => $game, 'body' => ['action' => 'bet', 'wager' => 10,
            'request_id' => bin2hex(random_bytes(16)), 'server_seed_hash' => $init['server_seed_hash']]];
    }
    Illuminate\Support\Facades\DB::table('users')->where('id', 1)->update(['balance' => 10]);
    $results = concurrentCalls($database, $payloads);
    check(count(array_filter($results, fn ($r) => $r['status'] === 'success')) === 1, 'MySQL simultaneous games cannot spend the same balance');
    check((float) VanguardLTE\User::find(1)->balance === 0.0, 'MySQL concurrent wagers never overdraw');
    echo "PASS: $checks total checks including MySQL concurrent bets and cashouts.\n";
} finally {
    // Name is generated or strictly validated above. Only this disposable fixture is removed.
    $pdo->exec("DROP DATABASE `$database`");
}
