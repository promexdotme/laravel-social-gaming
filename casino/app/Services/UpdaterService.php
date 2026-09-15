<?php

namespace VanguardLTE\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use ZipArchive;

class UpdaterService
{
    const CURRENT_VERSION = 'v2.5.0';
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
            'release_notes' => 'You are on the latest verified release.',
            'checked_at' => now()->toDateTimeString()
        ];

        try {
            // 1. Check Promex Hub first
            $response = Http::timeout(6)->get(self::HUB_VERSION_URL);
            if ($response->successful()) {
                $data = $response->json();
                $latest = $data['latest_version'] ?? self::CURRENT_VERSION;
                $updateAvailable = version_compare(ltrim($latest, 'v'), ltrim(self::CURRENT_VERSION, 'v'), '>');

                return [
                    'current_version' => self::CURRENT_VERSION,
                    'latest_version' => $latest,
                    'update_available' => $updateAvailable,
                    'release_notes' => $data['release_notes'] ?? 'General maintenance and stability improvements.',
                    'download_url' => $data['download_url'] ?? null,
                    'checked_at' => now()->toDateTimeString()
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("[UpdaterService] Hub version check failed: " . $e->getMessage());
        }

        return $status;
    }

    /**
     * Download and Apply Update (At Operator Risk)
     */
    public static function applyUpdate(): array
    {
        // 1. License Check
        if (!LicenseService::isLicensed()) {
            return [
                'success' => false,
                'message' => 'Live updates require an active verified Promex license.'
            ];
        }

        $check = self::checkUpdate();
        $downloadUrl = $check['download_url'] ?? null;
        if (empty($downloadUrl)) {
            $downloadUrl = "https://clients.377.live/api/service/packs/download?pack=core_update";
        }

        $tempDir = storage_path('app/updates');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipFile = $tempDir . '/update_' . time() . '.zip';

        try {
            // 2. Download bundle
            $license = LicenseService::getStatus();
            $key = $license['license_key'] ?? '';

            $response = Http::timeout(60)
                ->withHeaders(['X-License-Key' => $key])
                ->get($downloadUrl);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'message' => 'Failed to download update bundle from central hub (HTTP ' . $response->status() . ').'
                ];
            }

            file_put_contents($zipFile, $response->body());

            // 3. Extract and Apply Safe Files
            $zip = new ZipArchive();
            if ($zip->open($zipFile) !== true) {
                return [
                    'success' => false,
                    'message' => 'Corrupt update archive downloaded.'
                ];
            }

            // Exclude dangerous / sensitive files
            $excludedPatterns = [
                '.env',
                'storage/',
                'database/database.sqlite',
                'public/uploads/',
                'config/database.php'
            ];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);

                $skip = false;
                foreach ($excludedPatterns as $pattern) {
                    if (str_starts_with($filename, $pattern) || $filename === $pattern) {
                        $skip = true;
                        break;
                    }
                }

                if (!$skip) {
                    $zip->extractTo(base_path(), $filename);
                }
            }
            $zip->close();
            @unlink($zipFile);

            // 4. Run database migrations & clear caches
            try {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('view:clear');
                Artisan::call('route:clear');
                Artisan::call('config:clear');
            } catch (\Throwable $e) {
                Log::warning("[UpdaterService] Post-update commands warning: " . $e->getMessage());
            }

            return [
                'success' => true,
                'version' => $check['latest_version'] ?? self::CURRENT_VERSION,
                'message' => 'Platform updated successfully to ' . ($check['latest_version'] ?? self::CURRENT_VERSION) . '!'
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
