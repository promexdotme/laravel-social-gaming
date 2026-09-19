<?php

namespace VanguardLTE\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    const DEFAULT_SERVER = 'https://clients.377.live/api/service';
    const CACHE_KEY = 'cedar_system_license_status';

    /**
     * Promex Central Authority RSA-2048 Public Key
     * Verifies cryptographic claims signed exclusively by clients.377.live private key.
     */
    const PROMEX_PUBLIC_KEY = "-----BEGIN PUBLIC KEY-----\n"
        . "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA93DVNVelYPiqOMEZjoHO\n"
        . "BvcADoTX4gTo+aV19fEGMjmCEu+wWdr5CHJmp/mC1fuXmZlxjsOxgPr6KdWHln9t\n"
        . "Rbr9H0z73Ysggcw8Jf94KKYbZc7KeUkKAjGuW1oCVvt4Hi59UYX27d2wDF86U89x\n"
        . "cUPWLyK70ORAQ5cumYV3R7PVUoLIhYKiDwcz1SHW1qi/FwQd+YT9x9TjiMfeOOdG\n"
        . "1eVxzQfqpS5FFMdyorTwuRNxuRvQKndNGsLQIMShOxpvOXf/2z27toE5A99RM8zJ\n"
        . "J8Sp+yPOG3QFYodbpYO+cV+RhTiGF0ljTYP+GQJKFNnKNREN8pS1+8Wkbm9f779H\n"
        . "AQIDAQAB\n"
        . "-----END PUBLIC KEY-----";

    /** Cache signed envelopes, never a mutable "active" flag. Revalidate every read. */
    public static function getStatus(bool $forceRefresh = false): array
    {
        $key = (string)(function_exists('settings') ? settings('license_key', '') : '');
        $key = trim($key !== '' ? $key : (string)env('LICENSE_KEY', ''));
        $domain = self::licensedDomain();
        $cacheKey = self::CACHE_KEY . ':v2:' . hash('sha256', $domain . '|' . $key);
        if ($key === '' || $domain === '') {
            return self::denied($key, $domain, 'License key and a valid APP_URL are required.');
        }
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            $claims = SignedLicenseCertificate::verify($cached, (string)config('licensing.public_key', self::PROMEX_PUBLIC_KEY), $key, $domain, time(), !empty($cached['_offline']));
            if ($claims !== null) {
                return self::present($claims, $key, !empty($cached['_offline']));
            }
            Cache::forget($cacheKey);
        }
        try {
            $response = Http::timeout(6)->withOptions(['allow_redirects' => false])->withHeaders([
                'X-License-Key' => $key, 'X-Domain' => $domain, 'Accept' => 'application/json',
            ])->post(self::DEFAULT_SERVER . '/license/check', [
                'license_key' => $key, 'domain' => $domain, 'app_version' => '2.5.0', 'certificate_version' => 1,
            ]);
            if ($response->successful()) {
                $data = $response->json();
                $claims = is_array($data) ? SignedLicenseCertificate::verify($data, (string)config('licensing.public_key', self::PROMEX_PUBLIC_KEY), $key, $domain, time()) : null;
                if ($claims !== null) {
                    $envelope = ['signed_payload' => $data['signed_payload'], 'signature' => $data['signature']];
                    self::saveLocalCert($envelope);
                    Cache::put($cacheKey, $envelope, max(1, $claims['refresh_after'] - time()));
                    return self::present($claims, $key, false);
                }
                self::deleteLocalCert();
                return self::denied($key, $domain, 'Invalid, expired, or mismatched signed license certificate.');
            }
            // Only genuine availability failures qualify for previously signed offline grace.
            if ($response->status() !== 429 && $response->status() < 500) {
                self::deleteLocalCert();
                return self::denied($key, $domain, 'License authority denied verification.');
            }
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('[LicenseService] License authority unavailable.');
        } catch (\Throwable $e) {
            Log::error('[LicenseService] License verification failed.');
            return self::denied($key, $domain, 'License verification failed.');
        }
        $path = self::getCertPath();
        $envelope = is_file($path) ? json_decode((string)file_get_contents($path), true) : null;
        $claims = is_array($envelope) ? SignedLicenseCertificate::verify($envelope, (string)config('licensing.public_key', self::PROMEX_PUBLIC_KEY), $key, $domain, time(), true) : null;
        if ($claims === null) {
            return self::denied($key, $domain, 'No valid offline certificate; reconnect to verify your license.');
        }
        $envelope['_offline'] = true;
        Cache::put($cacheKey, $envelope, min(60, max(1, $claims['grace_deadline'] - time())));
        return self::present($claims, $key, true);
    }

    public static function licensedDomain(): string
    {
        $host = parse_url((string)config('app.url', ''), PHP_URL_HOST);
        return is_string($host) ? strtolower(rtrim($host, '.')) : '';
    }

    protected static function getCertPath(): string
    {
        return storage_path('framework/license.cert');
    }

    protected static function saveLocalCert(array $envelope): void
    {
        $path = self::getCertPath();
        $temp = $path . '.' . bin2hex(random_bytes(8)) . '.tmp';
        try {
            if (file_put_contents($temp, json_encode($envelope, JSON_THROW_ON_ERROR), LOCK_EX) === false) {
                throw new \RuntimeException('Certificate write failed');
            }
            @chmod($temp, 0600);
            if (!rename($temp, $path)) {
                throw new \RuntimeException('Certificate replacement failed');
            }
        } finally {
            if (is_file($temp)) { @unlink($temp); }
        }
    }

    protected static function deleteLocalCert(): void
    {
        $path = self::getCertPath();
        if (is_file($path)) { @unlink($path); }
    }

    private static function present(array $claims, string $key, bool $offline): array
    {
        $deadline = $offline ? $claims['grace_deadline'] : $claims['refresh_after'];
        return array_merge($claims, [
            'license_key' => $key, 'valid_until' => gmdate('c', $claims['expires_at']),
            'days_left' => max(0, (int)ceil(($claims['expires_at'] - time()) / 86400)),
            'access_deadline' => $deadline, 'offline' => $offline,
            'plan' => (string)($claims['plan'] ?? 'Licensed') . ($offline ? ' (Offline Grace)' : ''),
            'has_full_pack' => in_array('all', $claims['features'], true) || in_array('full_pack', $claims['features'], true),
            'message' => $offline ? 'Using verified, fixed offline grace.' : 'Signed license verified.',
            'renew_url' => 'https://promex.me', 'checked_at' => gmdate('c'),
        ]);
    }

    private static function denied(string $key, string $domain, string $message): array
    {
        return ['status' => 'unverified', 'plan' => 'Verification Required', 'license_key' => $key,
            'domain' => $domain, 'valid_until' => null, 'days_left' => 0, 'features' => [],
            'has_full_pack' => false, 'message' => $message, 'renew_url' => 'https://promex.me', 'checked_at' => gmdate('c')];
    }

    public static function hasFeature(string $feature): bool
    {
        $status = self::getStatus();
        return ($status['status'] ?? '') === 'active'
            && (in_array('all', $status['features'] ?? [], true) || in_array($feature, $status['features'] ?? [], true));
    }

    public static function canPlayGame(string $game): bool
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/D', $game)) { return false; }
        $status = self::getStatus();
        $features = $status['features'] ?? [];
        return ($status['status'] ?? '') === 'active'
            && (!isset($status['games']) || in_array($game, $status['games'], true))
            && (in_array('all', $features, true) || in_array('local_slots', $features, true) || in_array('full_pack', $features, true));
    }

    /**
     * Get Store Catalog (Add-ons, Game Packs, Modules)
     */
    public static function getStoreCatalog(): array
    {
        $status = self::getStatus();

        return [
            'modules' => [
                [
                    'id' => 'sportsbook_feed',
                    'name' => 'Real-Time Sportsbook Odds Feed',
                    'category' => 'Data Feeds',
                    'version' => '2.5.0',
                    'installed' => true,
                    'status' => $status['status'] === 'active' ? 'Operational' : 'Paused',
                    'description' => 'Live odds caching for UEFA, Premier League, NBA, and UFC via central Redis hub.',
                    'icon' => 'sports_soccer'
                ],
                [
                    'id' => 'prediction_markets',
                    'name' => 'Future Vote Prediction Engine',
                    'category' => 'Web3 & Predictions',
                    'version' => '2.1.0',
                    'installed' => true,
                    'status' => 'Operational',
                    'description' => 'Direct Polymarket Gamma API resolution with automated share settlement.',
                    'icon' => 'how_to_vote'
                ],
                [
                    'id' => 'affiliates_3tier',
                    'name' => '3-Tier Affiliate Commission Suite',
                    'category' => 'Marketing',
                    'version' => '2.0.0',
                    'installed' => true,
                    'status' => 'Operational',
                    'description' => 'Multi-tier automated revenue sharing with WhatsApp referral link tracking.',
                    'icon' => 'group_add'
                ],
                [
                    'id' => 'vip_loyalty',
                    'name' => 'VIP Club & Loyalty Vault',
                    'category' => 'Retention',
                    'version' => '1.5.0',
                    'installed' => true,
                    'status' => 'Operational',
                    'description' => 'Automated XP tiers, level-up bonuses, and instant rakeback claims.',
                    'icon' => 'diamond'
                ],
                [
                    'id' => 'xtopay_crypto',
                    'name' => 'XtoPay KYC-Free Crypto Gateway',
                    'category' => 'Cashier',
                    'version' => '3.0.0',
                    'installed' => true,
                    'status' => 'Operational',
                    'description' => 'Zero-fee USDT merchant gateway supporting TRC20, Polygon, and BSC.',
                    'icon' => 'currency_bitcoin'
                ]
            ],
            'game_packs' => [
                [
                    'id' => 'cedar_originals',
                    'name' => 'Cedar Provably-Fair Mini Games Pack',
                    'count' => 14,
                    'installed' => true,
                    'size' => '12 MB',
                    'games' => ['CedarCrash', 'CedarDice', 'CedarMines', 'CedarPlinko', 'CedarWheel', 'RoyalSteps',
                        'CedarLimbo', 'CedarTower', 'CedarKeno', 'CedarCoinFlip', 'CedarGoal', 'CedarTreasure',
                        'CedarHiLo', 'CedarBlackjack'],
                    'description' => 'Built-in provably fair Cedar Originals with server-authoritative RTP controls.'
                ],
                [
                    'id' => 'custom_slots_v1',
                    'name' => 'Custom Cedar HTML5 Slots Suite',
                    'count' => 5,
                    'installed' => true,
                    'size' => '85 MB',
                    'games' => ['RomeSlot', 'RoyalSteps', 'EgyptTreasuries', 'MidnightCity', 'VikingTime'],
                    'description' => 'Bespoke high-definition slot themes with custom RTP math engines.'
                ],
                [
                    'id' => 'pragmatic_pack_vol1',
                    'name' => 'Pragmatic Play Mega Pack (Vol. 1)',
                    'count' => 120,
                    'installed' => file_exists(base_path('../games/WolfGold')),
                    'size' => '4.2 GB',
                    'description' => 'Wolf Gold, Sweet Bonanza, Gates of Olympus, and 115+ classic titles.'
                ],
                [
                    'id' => 'amatic_classics',
                    'name' => 'Amatic Classic Fruit & Bells Pack',
                    'count' => 180,
                    'installed' => file_exists(base_path('../games/WolfMoonAM')),
                    'size' => '3.1 GB',
                    'description' => 'Complete retro classic arcade reel slot machines.'
                ],
                [
                    'id' => 'egt_jackpot_series',
                    'name' => 'EGT 4-Level Mystery Jackpot Series',
                    'count' => 95,
                    'installed' => file_exists(base_path('../games/ActionMoneyEGT')),
                    'size' => '2.8 GB',
                    'description' => 'Hot 20, 40 Super Hot, Flaming Hot with shared mystery card jackpots.'
                ]
            ]
        ];
    }

    /**
     * Check if client has an active valid license
     */
    public static function isLicensed(): bool
    {
        $status = self::getStatus();
        return ($status['status'] ?? '') === 'active';
    }

    /**
     * Perk 1: Check if client can use Hosted Games CDN
     */
    public static function canUseCdnGames(): bool
    {
        return self::isLicensed() && self::hasFeature('cdn_games');
    }

    /**
     * Perk 2: Check if client can download Liteback Store add-on packs
     */
    public static function canDownloadPacks(): bool
    {
        return self::isLicensed() && self::hasFeature('store_packs');
    }

    /**
     * Perk 3: Check if client can use free Central Sports Odds API
     */
    public static function canUseCentralOdds(): bool
    {
        return self::isLicensed() && self::hasFeature('sportsbook_hub');
    }

}
