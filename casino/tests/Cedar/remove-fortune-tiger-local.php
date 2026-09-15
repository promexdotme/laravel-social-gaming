<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../../vendor/autoload.php';
$app = require __DIR__ . '/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
if (!in_array(config('database.connections.mysql.host'), ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Local database required.');
$games = DB::table('games')->where('name', 'FortuneTiger')->get();
$ids = $games->pluck('id')->all();
$links = DB::table('game_categories')->whereIn('game_id', $ids)->get();
$backup = 'C:/Users/leban/.codex/backups/FortuneTiger-20260910/catalog.json';
if ($games->isNotEmpty()) {
    if (file_exists($backup)) throw new RuntimeException('Catalog backup already exists; inspect before retrying.');
    if (file_put_contents($backup, json_encode(['games' => $games, 'game_categories' => $links], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)) === false) throw new RuntimeException('Backup failed.');
}
DB::transaction(function () use ($ids) {
    DB::table('game_categories')->whereIn('game_id', $ids)->delete();
    DB::table('games')->whereIn('id', $ids)->delete();
});
echo 'Removed local catalog rows: ' . count($ids) . '; category links: ' . $links->count() . PHP_EOL;
