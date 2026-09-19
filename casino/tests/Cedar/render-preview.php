<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
// Renders actual Blade files against synthetic, unsaved display data. Does not log into an account.
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = new VanguardLTE\User();
$user->forceFill(['id' => 0, 'username' => 'Cedar local test', 'balance' => 100000, 'shop_id' => 1]);
Illuminate\Support\Facades\Auth::setUser($user);
$output = dirname(__DIR__, 3) . '/output/playwright/cedar';
if (!is_dir($output)) mkdir($output, 0777, true);
foreach (VanguardLTE\Services\CedarGameService::GAMES as $name) {
    $html = file_get_contents(dirname(__DIR__, 3) . '/games/' . $name . '/index.html');
    file_put_contents($output . '/' . $name . '.html', $html);
    echo $name . ' rendered.' . PHP_EOL;
}
