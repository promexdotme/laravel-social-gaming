<?php
// Standalone isolated regression suite: no app bootstrap, .env, DB, or live HTTP.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/authority-fixture.php';

use Illuminate\Cache\CacheManager;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Http;
use VanguardLTE\Services\LicenseService;
use VanguardLTE\Services\SignedLicenseCertificate as Certificate;
use VanguardLTE\Http\Middleware\ProtectGameRequests;
use VanguardLTE\Services\GameRuntimeSession;

$checks = 0;
function check($condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    ++$checks;
}
$keyOptions = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => __DIR__ . '/openssl-test.cnf'];
$private = openssl_pkey_new($keyOptions);
if (!$private) { throw new RuntimeException('OpenSSL test-key generation unavailable'); }
openssl_pkey_export($private, $privatePem, null, $keyOptions);
$public = openssl_pkey_get_details($private)['key'];
$now = time();
$license = ['license_key' => 'test-only', 'status' => 'active', 'plan' => 'Test', 'features' => '["local_slots"]', 'valid_until' => gmdate('c', $now + 604800)];
$envelope = issueTestCertificate($license, 'audit.invalid', $privatePem, $now);
$claims = json_decode($envelope['signed_payload'], true);
function signed(array $claims): array {
    global $private;
    $payload = json_encode($claims);
    openssl_sign($payload, $sig, $private, OPENSSL_ALGO_SHA256);
    return ['signed_payload' => $payload, 'signature' => base64_encode($sig)];
}
$verify = fn ($env, $at = null, $offline = false, $key = 'test-only', $domain = 'audit.invalid') => Certificate::verify($env, $public, $key, $domain, $at ?? $now, $offline);
check($verify($envelope) !== null, 'real authority signature accepted');
check($verify($envelope, null, false, 'different-key') === null, 'wrong key rejected');
check($verify($envelope, null, false, 'test-only', 'copy.invalid') === null, 'copied domain rejected');
check($verify($envelope, $now + 3600) === null, 'online refresh boundary rejected');
check($verify($envelope, $now + 3600, true) !== null, 'fixed offline grace accepted');
check($verify($envelope, $now + 259200, true) === null, 'offline grace expires exactly');
check($verify(['signed_payload' => $envelope['signed_payload'] . ' ', 'signature' => $envelope['signature']]) === null, 'payload tampering rejected');
check($verify(['signed_payload' => $envelope['signed_payload'], 'signature' => '%%%']) === null, 'malformed signature rejected');
foreach (['status' => 'suspended', 'product' => 'other', 'version' => 2, 'issued_at' => $now + 300,
    'grace_deadline' => $now + 259201, 'expires_at' => $now - 1, 'features' => ['all', 42]] as $field => $value) {
    check($verify(signed(array_replace($claims, [$field => $value])), null, true) === null, 'invalid signed ' . $field . ' rejected');
}
check($verify(signed(array_replace($claims, ['runtime_seed' => 'invalid']))) === null, 'invalid signed runtime seed rejected');

$temp = sys_get_temp_dir() . '/promex-license-test-' . bin2hex(random_bytes(8));
mkdir($temp . '/framework', 0700, true);
$app = new Application(dirname(__DIR__, 2));
$app->useStoragePath($temp);
$app->instance('config', new Repository([
    'app' => ['url' => 'https://audit.invalid', 'key' => 'unused-test-key'],
    'licensing' => ['public_key' => $public],
    'cache' => ['default' => 'array', 'stores' => ['array' => ['driver' => 'array', 'serialize' => true]], 'prefix' => 'test'],
]));
$settings = new class { public $key = 'test-only'; public function get($name, $default = null) { return $name === 'license_key' ? $this->key : $default; } };
$app->instance('anlutro\LaravelSettings\SettingStore', $settings);
$app->instance('log', new Psr\Log\NullLogger());
$app->instance('cache', new CacheManager($app));
$app->instance(Illuminate\Contracts\Routing\ResponseFactory::class, new Illuminate\Routing\ResponseFactory(
    Mockery::mock(Illuminate\Contracts\View\Factory::class),
    new Illuminate\Routing\Redirector(new Illuminate\Routing\UrlGenerator(new Illuminate\Routing\RouteCollection(), Request::create('https://audit.invalid')))
));
Facade::setFacadeApplication($app);
function hub($response): void {
    Http::swap(new Factory());
    Http::preventStrayRequests();
    Http::fake(['*' => $response]);
}
try {
    hub(Http::response($envelope, 200));
    check(LicenseService::getStatus(true)['status'] === 'active', 'service accepts signed authority response');
    check(LicenseService::canPlayGame('AuditGame'), 'licensed game accepted');
    check(!LicenseService::canDownloadPacks(), 'unbought feature denied');
    hub(Http::response([], 500));
    check(LicenseService::getStatus(true)['offline'] === true, 'outage uses signed certificate');
    $settings->key = 'copied-key';
    check(!LicenseService::isLicensed(), 'offline certificate cannot move between license keys');
    $settings->key = 'test-only';
    hub(Http::response([], 403));
    check(LicenseService::getStatus(true)['status'] !== 'active', 'revocation denies');
    check(!file_exists($temp . '/framework/license.cert'), 'revocation removes offline certificate');
    hub(Http::response([], 500));
    check(LicenseService::getStatus(true)['status'] !== 'active', 'outage without certificate denies');
    hub(Http::response(['status' => 'active', 'features' => ['all']], 200));
    check(LicenseService::getStatus(true)['status'] !== 'active', 'unsigned hub denies');
    foreach ([302, 401, 404, 429, 500] as $status) {
        hub(Http::response([], $status));
        check(LicenseService::getStatus(true)['status'] !== 'active', 'HTTP ' . $status . ' without certificate denies');
    }
    hub(Http::response(signed(array_replace($claims, ['domain' => 'copy.invalid'])), 200));
    check(LicenseService::getStatus(true)['status'] !== 'active', 'wrong-domain hub denies');
    hub(Http::response($envelope, 200));
    check(LicenseService::getStatus(true)['status'] === 'active', 'reactivation succeeds');
    $cacheKey = LicenseService::CACHE_KEY . ':v2:' . hash('sha256', 'audit.invalid|test-only');
    $expired = signed(array_replace($claims, ['issued_at' => $now - 5000, 'refresh_after' => $now - 4000, 'grace_deadline' => $now - 1, 'expires_at' => $now - 1]));
    Cache::put($cacheKey, $expired, 3600);
    file_put_contents($temp . '/framework/license.cert', json_encode($expired));
    hub(Http::response([], 500));
    check(!LicenseService::isLicensed(), 'cached signature revalidated at expiry');
    hub(Http::response(signed(array_replace($claims, ['games' => ['OnlyThisGame']])), 200));
    LicenseService::getStatus(true);
    check(!LicenseService::canPlayGame('AuditGame'), 'signed game restriction enforced');
    check(LicenseService::canPlayGame('OnlyThisGame'), 'purchased game allowed');
    hub(Http::response($envelope, 200)); LicenseService::getStatus(true);

    $session = new Store('test', new ArraySessionHandler(120)); $session->start();
    $makeRequest = function ($id = null, $host = 'audit.invalid', $authenticated = true, $body = '{"bet":10}', $signedBody = null) use ($session) {
        $r = Request::create('https://' . $host . '/game/AuditGame/server', 'POST', [], [], [], [], $body);
        $r->setLaravelSession($session);
        $r->setUserResolver(fn () => $authenticated ? new Illuminate\Auth\GenericUser(['id' => 42]) : null);
        $route = new Illuminate\Routing\Route('POST', 'game/{game}/server', fn () => null); $route->bind($r);
        $r->setRouteResolver(fn () => $route);
        $r->headers->set('X-Promex-Request', $id ?? bin2hex(random_bytes(16)));
        $r->headers->set('X-Promex-Time', (string)time());
        $r->headers->set('X-CSRF-TOKEN', $session->token());
        if ($authenticated && $host === 'audit.invalid') {
            $runtime = GameRuntimeSession::issue($r, 'AuditGame');
            $sequence = '1';
            $canonical = "POST\n/game/AuditGame/server\n" . $r->headers->get('X-Promex-Request') . "\n"
                . $r->headers->get('X-Promex-Time') . "\n{$sequence}\n" . ($signedBody ?? $r->getContent());
            $r->headers->set('X-Promex-Protocol', '2');
            $r->headers->set('X-Promex-Expires', (string)$runtime['expires']);
            $r->headers->set('X-Promex-Sequence', $sequence);
            $r->headers->set('X-Promex-Proof', hash_hmac('sha256', $canonical, base64_decode($runtime['key'], true)));
        }
        return $r;
    };
    $guard = new ProtectGameRequests();
    $next = fn () => new Illuminate\Http\Response('engine executed', 200);
    $r = $makeRequest();
    check($guard->handle($r, $next)->getStatusCode() === 200, 'authenticated entitled request reaches engine');
    check($guard->handle($r, $next)->getStatusCode() === 409, 'duplicate blocked before engine');
    $r = $makeRequest(); $r->headers->remove('X-Promex-Proof');
    check($guard->handle($r, $next)->getStatusCode() === 403, 'request without compiled runtime proof rejected');
    $r = $makeRequest(null, 'audit.invalid', true, '{"bet":11}', '{"bet":10}');
    check($guard->handle($r, $next)->getStatusCode() === 403, 'body tampering after runtime signing rejected');
    $r = $makeRequest(); $r->headers->set('X-Promex-Time', (string)(time() - 121));
    check($guard->handle($r, $next)->getStatusCode() === 400, 'old request rejected');
    $r = $makeRequest(); $r->headers->set('Origin', 'https://evil.invalid');
    check($guard->handle($r, $next)->getStatusCode() === 403, 'cross origin denied');
    check($guard->handle($makeRequest(null, 'copy.invalid'), $next)->getStatusCode() === 403, 'wrong host denied');
    check($guard->handle($makeRequest(null, 'audit.invalid', false), $next)->getStatusCode() === 401, 'anonymous denied');
    $r = $makeRequest(); $r->server->set('HTTPS', 'off');
    check($guard->handle($r, $next)->getStatusCode() === 403, 'production requires HTTPS');
    $encrypter = new Illuminate\Encryption\Encrypter(random_bytes(32), 'AES-256-CBC');
    $csrf = new class($app, $encrypter) extends VanguardLTE\Http\Middleware\VerifyCsrfToken {
        protected $addHttpCookie = false;
        protected function runningUnitTests() { return false; }
    };
    check($csrf->handle($makeRequest(), $next)->getStatusCode() === 200, 'Laravel accepts session CSRF');
    $r = $makeRequest(); $r->headers->remove('X-CSRF-TOKEN');
    $rejected = false;
    try { $csrf->handle($r, $next); } catch (Illuminate\Session\TokenMismatchException $e) { $rejected = true; }
    check($rejected, 'game route no longer exempt from CSRF');
    $r = Request::create('https://audit.invalid/liteback/store/license', 'POST');
    $r->setLaravelSession($session);
    $rejected = false;
    try { $csrf->handle($r, $next); } catch (Illuminate\Session\TokenMismatchException $e) { $rejected = true; }
    check($rejected, 'license settings cannot inherit Liteback CSRF exemption');
    $inject = new VanguardLTE\Http\Middleware\InjectGameHomeButton();
    $res = $inject->handle($makeRequest(), fn () => new Illuminate\Http\Response('<html><head><script src="game.js"></script></head><body></body></html>', 200, ['Content-Type' => 'text/html']));
    check(strpos($res->getContent(), 'game-session.js') < strpos($res->getContent(), 'game.js'), 'session transport precedes game scripts');
    check(str_contains($res->headers->get('Cache-Control'), 'no-store'), 'session page not cacheable');
    echo "PASS: {$checks} licensing/session regression checks\n";
} finally {
    // Remove only the explicit files in this test-owned directory.
    foreach (glob($temp . '/framework/*') as $file) { if (is_file($file)) unlink($file); }
    rmdir($temp . '/framework'); rmdir($temp);
}
