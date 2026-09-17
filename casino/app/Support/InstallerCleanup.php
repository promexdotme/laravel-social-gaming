<?php

namespace VanguardLTE\Support;

/** Final installation step. Never accepts paths or filenames from an HTTP request. */
final class InstallerCleanup
{
    public static function run(string $installationRoot): array
    {
        $root = realpath($installationRoot);
        if ($root === false || !is_dir($root)) {
            throw new \RuntimeException('Invalid installation directory.');
        }
        $lock = $root . DIRECTORY_SEPARATOR . 'installed.lock';
        if (!is_file($lock) || is_link($lock) || realpath(dirname($lock)) !== $root) {
            throw new \RuntimeException('Installation must finish before cleanup can run.');
        }
        $result = ['removed' => [], 'failed' => []];
        // Delete the executing installer last. Keep configuration, runtime certificates and the lock.
        foreach (['install.sql', 'database_backup.sql', 'promex-gaming-suite-v2.0-cpanel.zip', 'install.php'] as $name) {
            if ($name === 'install.php' && $result['failed']) {
                $result['failed'][] = $name;
                continue; // Keep the locked installer available for a cleanup retry.
            }
            $path = $root . DIRECTORY_SEPARATOR . $name;
            if (!file_exists($path) && !is_link($path)) { continue; }
            $resolved = realpath($path);
            if (is_link($path) || !is_file($path) || $resolved === false || dirname($resolved) !== $root
                || !@unlink($path)) {
                $result['failed'][] = $name;
                continue;
            }
            $result['removed'][] = $name;
        }
        return $result;
    }
}
