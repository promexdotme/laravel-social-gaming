<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../../app/Support/InstallerCleanup.php';
use VanguardLTE\Support\InstallerCleanup;

$root = sys_get_temp_dir() . '/promex-installer-test-' . bin2hex(random_bytes(8));
mkdir($root, 0700);
$checks = 0;
$assert = function ($ok, $label) use (&$checks) {
    if (!$ok) { throw new RuntimeException($label); }
    ++$checks;
};
try {
    file_put_contents($root . '/install.php', 'test installer');
    file_put_contents($root . '/install.sql', 'test SQL');
    $blocked = false;
    try { InstallerCleanup::run($root); } catch (RuntimeException $e) { $blocked = true; }
    $assert($blocked && is_file($root . '/install.sql'), 'Incomplete installation must not delete anything');
    file_put_contents($root . '/installed.lock', 'Test installation complete');
    file_put_contents($root . '/database_backup.sql', 'test fallback SQL');
    file_put_contents($root . '/promex-gaming-suite-v2.0-cpanel.zip', 'test release archive');
    file_put_contents($root . '/.env', 'test configuration');
    file_put_contents($root . '/license.cert', 'test runtime certificate');
    file_put_contents($root . '/unrelated.sql', 'keep unrelated file');
    $result = InstallerCleanup::run($root);
    $assert(count($result['removed']) === 4 && !$result['failed'], 'All four installation artifacts removed');
    $assert(is_file($root . '/installed.lock') && is_file($root . '/.env') && is_file($root . '/license.cert') && is_file($root . '/unrelated.sql'), 'Runtime files and unrelated SQL preserved');
    $assert(InstallerCleanup::run($root) === ['removed' => [], 'failed' => []], 'Repeated cleanup is harmless');
    mkdir($root . '/install.sql');
    file_put_contents($root . '/install.php', 'test installer');
    $result = InstallerCleanup::run($root);
    $assert($result['failed'] === ['install.sql', 'install.php'] && is_file($root . '/install.php'), 'Failed cleanup keeps locked installer for retry');
    rmdir($root . '/install.sql');
    $result = InstallerCleanup::run($root);
    $assert($result['removed'] === ['install.php'], 'Retry finishes cleanup');
    // Exercise a PHP process deleting its own script, as the successful installer does.
    $helper = var_export(realpath(__DIR__ . '/../../app/Support/InstallerCleanup.php'), true);
    file_put_contents($root . '/install.php', '<?php require ' . $helper . '; echo json_encode(\\VanguardLTE\\Support\\InstallerCleanup::run(__DIR__)); echo "DONE";');
    $process = proc_open([PHP_BINARY, $root . '/install.php'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fclose($pipes[0]); $output = stream_get_contents($pipes[1]); $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]); $code = proc_close($process);
    $assert($code === 0 && str_ends_with($output, 'DONE') && !is_file($root . '/install.php'), 'Self-deletion preserves completion response: ' . $error);
    echo "PASS: {$checks} installer cleanup checks; no real database or installation touched\n";
} finally {
    foreach (scandir($root) as $name) {
        if ($name === '.' || $name === '..') continue;
        $path = $root . '/' . $name;
        if (is_dir($path)) rmdir($path); else unlink($path);
    }
    rmdir($root);
}
