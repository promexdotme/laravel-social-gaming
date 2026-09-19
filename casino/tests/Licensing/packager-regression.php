<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../../../tools/packaging/verify_prepack.php';
$root = realpath(__DIR__ . '/../../..');
$archive = sys_get_temp_dir() . '/promex-prepack-test-' . bin2hex(random_bytes(8)) . '.zip';
$required = ['.htaccess', 'install.php', 'casino/app/Support/InstallerCleanup.php', 'js/game-session.js', 'js/promex-html-game.js', 'js/ws-bridge.js', 'js/ws-bridge.wasm',
    'casino/app/Services/LicenseService.php', 'casino/app/Services/SignedLicenseCertificate.php', 'casino/app/Services/GameRuntimeSession.php',
    'casino/app/Http/Middleware/ProtectGameRequests.php', 'casino/app/Http/Middleware/VerifyCsrfToken.php',
    'casino/app/Http/Middleware/InjectGameHomeButton.php', 'casino/config/licensing.php',
    'casino/routes/web.php', 'casino/app/Http/Controllers/Web/Frontend/GamesController.php'];
if (!str_contains(file_get_contents($root . '/.htaccess'), 'RewriteRule ^js/mock-websocket\\.js$ js/ws-bridge.js')) {
    throw new RuntimeException('Legacy mock-websocket URL does not map to compiled bridge');
}
try {
    $zip = new ZipArchive(); $zip->open($archive, ZipArchive::CREATE);
    foreach ($required as $name) { $zip->addFile($root . '/' . $name, $name); }
    $zip->close();
    verifyPrepack($archive, $root);
    foreach (['js/mock-websocket.js', 'casino/.env', 'casino/storage/framework/license.cert',
        'casino/storage/framework/sessions/session-id', 'casino/bootstrap/cache/config.php',
        'localscripts/licensing_hub/private.key', 'old.zip', 'casino/routes/web.php.bak', 'tools/wasm/packet-codec.wat'] as $forbidden) {
        $zip->open($archive); $zip->addFromString($forbidden, 'test-only'); $zip->close();
        $rejected = false;
        try { verifyPrepack($archive, $root); } catch (RuntimeException $e) { $rejected = true; }
        if (!$rejected) { throw new RuntimeException('Forbidden archive entry accepted: ' . $forbidden); }
        $zip->open($archive); $zip->deleteName($forbidden); $zip->close();
    }
    $zip->open($archive); $zip->addFromString('js/ws-bridge.wasm', 'stale'); $zip->close();
    $rejected = false;
    try { verifyPrepack($archive, $root); } catch (RuntimeException $e) { $rejected = true; }
    if (!$rejected) { throw new RuntimeException('Stale release accepted'); }
    echo "PASS: package rejects mock, credentials, runtime state, authority source and stale binary\n";
} finally { if (is_file($archive)) unlink($archive); }
