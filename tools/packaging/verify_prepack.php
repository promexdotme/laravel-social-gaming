<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function verifyPrepack(string $archive, string $root): void
{
    $zip = new ZipArchive();
    if ($zip->open($archive) !== true) { throw new RuntimeException('Cannot read prepack'); }
    try {
        $required = ['.htaccess', 'install.php', 'casino/app/Support/InstallerCleanup.php', 'js/game-session.js', 'js/ws-bridge.js', 'js/ws-bridge.wasm',
            'casino/app/Services/LicenseService.php', 'casino/app/Services/SignedLicenseCertificate.php',
            'casino/app/Http/Middleware/ProtectGameRequests.php', 'casino/app/Http/Middleware/VerifyCsrfToken.php',
            'casino/app/Http/Middleware/InjectGameHomeButton.php', 'casino/config/licensing.php',
            'casino/routes/web.php', 'casino/app/Http/Controllers/Web/Frontend/GamesController.php'];
        foreach ($required as $name) {
            $bytes = $zip->getFromName($name);
            if ($bytes === false || !hash_equals(hash_file('sha256', $root . '/' . $name), hash('sha256', $bytes))) {
                throw new RuntimeException('Missing or stale release file: ' . $name);
            }
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = str_replace('\\', '/', $zip->getNameIndex($i));
            if (str_ends_with($name, '/')) { continue; }
            if (preg_match('~(^|/)(\.env(?:\.[^/]*)?|\.git)(/|$)|^localscripts/|^tools/|^casino/tests/|\.(?:zip|bak|old|orig|save|swp)$|^js/mock-websocket\.js$|^casino/bootstrap/cache/.*\.php$|^casino/storage/framework/license\.cert(?:\.|$)|^casino/storage/framework/(cache|sessions|views)/(?!.*\.gitkeep$)|^casino/storage/app/updates/~', $name)) {
                throw new RuntimeException('Forbidden development/runtime file in prepack: ' . $name);
            }
        }
    } finally { $zip->close(); }
    echo "PASS: prepack required-file hashes and runtime/source exclusions\n";
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    verifyPrepack($argv[1] ?? (__DIR__ . '/../../localscripts/dist/promex-gaming-suite-v2.0-cpanel.zip'), realpath(__DIR__ . '/../..'));
}
