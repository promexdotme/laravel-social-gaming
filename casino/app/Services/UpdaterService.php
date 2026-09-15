<?php

namespace VanguardLTE\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class UpdaterService
{
    const CURRENT_VERSION = 'v2.0';
    const GITHUB_REPO = 'promexdotme/laravel-social-gaming';
    const HUB_VERSION_URL = 'https://clients.377.live/api/service/version';

    /**
     * Get Current Installed Version
     */
    public static function getCurrentVersion(): string
    {
        return self::CURRENT_VERSION;
    }

    /**
     * Check for Updates via Hub or GitHub
     */
    public static function checkUpdate(): array
    {
        $status = [
            'current_version' => self::CURRENT_VERSION,
            'latest_version' => self::CURRENT_VERSION,
            'update_available' => false,
            'release_notes' => 'You are on the latest verified release (v2.0).',
            'download_url' => 'https://github.com/' . self::GITHUB_REPO . '/archive/refs/heads/main.zip',
            'checked_at' => now()->toDateTimeString()
        ];

        // 1. Check Promex Hub first
        try {
            $response = Http::timeout(6)->get(self::HUB_VERSION_URL);
            if ($response->successful()) {
                $data = $response->json();
                $latest = $data['latest_version'] ?? self::CURRENT_VERSION;
                $updateAvailable = version_compare(ltrim($latest, 'v'), ltrim(self::CURRENT_VERSION, 'v'), '>');

                if ($updateAvailable) {
                    return [
                        'current_version' => self::CURRENT_VERSION,
                        'latest_version' => $latest,
                        'update_available' => true,
                        'release_notes' => $data['release_notes'] ?? 'New verified release available from Hub.',
                        'download_url' => $data['download_url'] ?? ('https://github.com/' . self::GITHUB_REPO . '/archive/refs/heads/main.zip'),
                        'checked_at' => now()->toDateTimeString()
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[UpdaterService] Hub version check failed: " . $e->getMessage());
        }

        // 2. Check GitHub Tags
        try {
            $ghResponse = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'Promex-Gaming-Suite'])
                ->get('https://api.github.com/repos/' . self::GITHUB_REPO . '/tags');

            if ($ghResponse->successful()) {
                $tags = $ghResponse->json();
                if (!empty($tags) && isset($tags[0]['name'])) {
                    $latestTag = $tags[0]['name'];
                    $updateAvailable = version_compare(ltrim($latestTag, 'v'), ltrim(self::CURRENT_VERSION, 'v'), '>');

                    return [
                        'current_version' => self::CURRENT_VERSION,
                        'latest_version' => $latestTag,
                        'update_available' => $updateAvailable,
                        'release_notes' => $updateAvailable ? "New release ({$latestTag}) available on GitHub!" : 'System is up to date with latest GitHub release.',
                        'download_url' => 'https://github.com/' . self::GITHUB_REPO . '/archive/refs/heads/main.zip',
                        'checked_at' => now()->toDateTimeString()
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[UpdaterService] GitHub tags check failed: " . $e->getMessage());
        }

        return $status;
    }

    /**
     * Download and Apply Update Safely (Preserving .env and storage)
     */
    public static function applyUpdate(): array
    {
        $check = self::checkUpdate();
        $downloadUrl = $check['download_url'] ?? ('https://github.com/' . self::GITHUB_REPO . '/archive/refs/heads/main.zip');

        $tempDir = storage_path('app/updates');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipFile = $tempDir . '/update_' . time() . '.zip';

        try {
            // 1. Download bundle
            $request = Http::timeout(120)->withHeaders(['User-Agent' => 'Promex-Gaming-Suite']);

            if (str_contains($downloadUrl, 'clients.377.live')) {
                $license = LicenseService::getStatus();
                $key = $license['license_key'] ?? '';
                $request = $request->withHeaders(['X-License-Key' => $key]);
            }

            $response = $request->get($downloadUrl);
            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to download update bundle (HTTP ' . $response->status() . ').'
                ];
            }

            file_put_contents($zipFile, $response->body());

            // 2. Open archive
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) {
                @unlink($zipFile);
                return [
                    'success' => false,
                    'message' => 'Corrupt update archive downloaded.'
                ];
            }

            // 3. Detect top directory if GitHub zipball
            $topDir = '';
            for ($i = 0; $i < min(5, $zip->numFiles); $i++) {
                $entryName = $zip->getNameIndex($i);
                if (preg_match('#^([^/]+)/#', $entryName, $m)) {
                    $topDir = $m[1] . '/';
                    break;
                }
            }

            $excludedPrefixes = [
                '.env',
                'casino/.env',
                'storage/',
                'casino/storage/',
                'public/uploads/',
                'casino/public/uploads/',
                '_access/',
                '_packager/',
                'dist/',
                'install.php',
                'install.sql',
                'installed.lock',
                'database_backup.sql',
            ];

            $updatedFiles = 0;
            $casinoBasePath = realpath(base_path());
            $projectRoot = realpath(base_path('..'));

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $rawName = $zip->getNameIndex($i);
                $relPath = $rawName;

                if (!empty($topDir) && str_starts_with($rawName, $topDir)) {
                    $relPath = substr($rawName, strlen($topDir));
                }

                if (empty($relPath) || str_ends_with($relPath, '/')) {
                    continue;
                }

                $skip = false;
                foreach ($excludedPrefixes as $exc) {
                    if ($relPath === $exc || str_starts_with($relPath, $exc)) {
                        $skip = true;
                        break;
                    }
                }
                if ($skip) {
                    continue;
                }

                $targetFile = null;
                if (str_starts_with($relPath, 'casino/')) {
                    $targetFile = $casinoBasePath . '/' . substr($relPath, 7);
                } elseif (str_starts_with($relPath, 'app/') || str_starts_with($relPath, 'resources/') || str_starts_with($relPath, 'routes/') || str_starts_with($relPath, 'config/') || str_starts_with($relPath, 'database/')) {
                    $targetFile = $casinoBasePath . '/' . $relPath;
                } else {
                    $targetFile = $projectRoot . '/' . $relPath;
                }

                if ($targetFile) {
                    $targetDir = dirname($targetFile);
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }

                    $stream = $zip->getStream($rawName);
                    if ($stream) {
                        file_put_contents($targetFile, stream_get_contents($stream));
                        fclose($stream);
                        $updatedFiles++;
                    }
                }
            }

            $zip->close();
            @unlink($zipFile);

            // 4. Run database migrations & clear caches
            try {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('optimize:clear');
            } catch (\Throwable $e) {
                Log::warning("[UpdaterService] Post-update commands warning: " . $e->getMessage());
            }

            return [
                'success' => true,
                'version' => $check['latest_version'] ?? self::CURRENT_VERSION,
                'message' => "Successfully updated {$updatedFiles} files and ran pending database migrations! Platform is up to date."
            ];

        } catch (\Throwable $e) {
            Log::error("[UpdaterService] Update error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Update encountered an error: ' . $e->getMessage()
            ];
        }
    }
}