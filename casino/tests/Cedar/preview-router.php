<?php
// Run only with PHP's loopback development server. Uses a disposable SQLite fixture, not app accounts.
if (PHP_SAPI !== 'cli-server' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
    http_response_code(403); exit;
}
$root = dirname(__DIR__, 3);
$output = $root . '/output/playwright/cedar';
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (preg_match('~^/game/(CedarDice|CedarWheel|CedarPlinko|CedarMines|CedarCrash|RoyalSteps|CedarLimbo|CedarTower|CedarKeno|CedarCoinFlip|CedarGoal|CedarTreasure|CedarHiLo|CedarBlackjack)/server$~', $path, $match)) {
    define('CEDAR_TEST_DB', $output . '/fixture-v3.sqlite');
    if (!file_exists(CEDAR_TEST_DB)) touch(CEDAR_TEST_DB);
    require __DIR__ . '/fixture-bootstrap.php';
    header('Content-Type: application/json');
    $payload = json_decode(file_get_contents('php://input'), true) ?: [];
    echo json_encode((new VanguardLTE\Services\CedarGameService())->handle(new Illuminate\Http\Request($payload), $match[1]));
    return;
}
if (preg_match('~^/game/(CedarDice|CedarWheel|CedarPlinko|CedarMines|CedarCrash|RoyalSteps|CedarLimbo|CedarTower|CedarKeno|CedarCoinFlip|CedarGoal|CedarTreasure|CedarHiLo|CedarBlackjack)$~', $path, $match)) {
    header('Content-Type: text/html; charset=utf-8'); readfile($output . '/' . $match[1] . '.html'); return;
}
if ($path === '/js/cedar-client.js' || $path === '/js/mock-websocket.js') {
    header('Content-Type: application/javascript'); readfile($root . $path); return;
}
http_response_code(404);
