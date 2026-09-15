<?php
if (!in_array(PHP_SAPI, ['cli', 'cli-server'], true)) { http_response_code(404); exit; }
// Isolated SQLite regression harness. Never boots the app or reads its .env.
require __DIR__ . '/../../vendor/autoload.php';

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

$container = new Container();
Container::setInstance($container);
Facade::setFacadeApplication($container);
$db = new Manager($container);
$db->addConnection(defined('CEDAR_TEST_CONNECTION') ? CEDAR_TEST_CONNECTION : ['driver' => 'sqlite', 'database' => defined('CEDAR_TEST_DB') ? CEDAR_TEST_DB : ':memory:', 'prefix' => 'w_']);
$db->setAsGlobal();
$db->setEventDispatcher(new Dispatcher($container));
$db->bootEloquent();
$container->instance('db', $db->getDatabaseManager());
$container->bind('db.schema', fn () => $db->schema());
$container->instance('log', new Psr\Log\NullLogger());
$auth = new class {
    public ?int $userId = 1;
    public function check() { return $this->userId !== null; }
    public function id() { return $this->userId; }
};
$container->instance('auth', $auth);
$settings = new class {
    public array $values = [];
    public function get($key, $default = null) { return $this->values[$key] ?? $default; }
};
$container->instance('anlutro\LaravelSettings\SettingStore', $settings);
$container->instance('cache', new class {
    public array $values = [];
    public function get($key) { return $this->values[$key] ?? null; }
    public function forget($key) { unset($this->values[$key]); }
});
$db->connection()->setTransactionManager(new Illuminate\Database\DatabaseTransactionsManager());

if (!$db->schema()->hasTable('users')) {
$db->schema()->create('users', function (Blueprint $t) {
    $t->id(); $t->string('username')->default('test'); $t->string('status')->default('Active');
    $t->boolean('is_demo_agent')->default(false); $t->boolean('is_blocked')->default(false); $t->integer('shop_id')->default(1); $t->integer('parent_id')->default(0);
    foreach (['balance', 'unclaimed_commissions', 'total_affiliate_earnings', 'unclaimed_rakeback', 'total_rakeback_claimed'] as $col) $t->decimal($col, 20, 4)->default(0);
    $t->integer('vip_xp')->default(0); $t->string('vip_level')->default('Bronze'); $t->text('claimed_level_bonuses')->nullable(); $t->timestamps();
});
$db->schema()->create('games', function (Blueprint $t) { $t->id(); $t->string('name'); $t->integer('shop_id')->default(1); $t->integer('view')->default(1); });
$db->schema()->create('stat_game', function (Blueprint $t) {
    $t->id(); $t->integer('user_id'); $t->decimal('balance', 20, 2); $t->decimal('bet', 20, 2); $t->decimal('win', 20, 2);
    $t->string('game'); $t->integer('in_game'); $t->integer('shop_id'); $t->dateTime('date_time');
});
$db->schema()->create('affiliate_commissions', function (Blueprint $t) {
    $t->id(); foreach (['affiliate_id', 'referred_user_id', 'tier'] as $c) $t->integer($c);
    $t->string('game_type'); foreach (['wager_amount', 'commission_rate', 'commission_amount'] as $c) $t->decimal($c, 20, 4);
    $t->string('status'); $t->timestamps();
});
$db->schema()->create('vip_claims', function (Blueprint $t) {
    $t->id(); $t->integer('user_id'); $t->string('type'); $t->string('tier'); $t->decimal('amount', 20, 4); $t->timestamps();
});
$migration = require __DIR__ . '/../../database/migrations/2026_09_10_000001_create_cedar_rounds.php';
$migration->up();
$accountingMigration = require __DIR__ . '/../../database/migrations/2026_09_10_000002_harden_cedar_accounting.php';
$accountingMigration->up();
// Unrelated legacy user observers require the entire old casino schema; exclude only those observers.
User::flushEventListeners();
DB::table('users')->insert([['id' => 1, 'balance' => 100000, 'parent_id' => 2], ['id' => 2, 'balance' => 0, 'parent_id' => 0]]);
foreach (CedarGameService::GAMES as $game) DB::table('games')->insert(['name' => $game]);
}
User::flushEventListeners();
