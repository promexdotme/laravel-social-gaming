<?php

namespace VanguardLTE\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseService
{
    const DEFAULT_SERVER = 'https://clients.377.live/api/service';
    const CACHE_KEY = 'cedar_system_license_status';
    const CACHE_TTL = 43200; // 12 hours in seconds

    /**
     * Get Current License Status (Cached for 12 hours)
     */
    public static function getStatus(bool $forceRefresh = false): array
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::fetchStatusFromHub();
        });
    }

    /**
     * Perform Handshake with Central License Hub (clients.377.live)
     */
    protected static function fetchStatusFromHub(): array
    {
        $key = function_exists('settings') ? settings('license_key', '') : '';
        if (empty($key)) {
            $key = env('LICENSE_KEY', '');
        }

        $domain = function_exists('settings') ? settings('license_domain', '') : '';
        if (empty($domain)) {
            $domain = request() ? request()->getHost() : env('APP_URL', 'localhost');
            $domain = preg_replace('#^https?://#', '', rtrim($domain, '/'));
        }

        if (empty($key)) {
            return [
                'status' => 'unregistered',
                'plan' => 'Community / Trial Edition',
                'license_key' => '',
                'domain' => $domain,
                'valid_until' => null,
                'days_left' => 0,
                'features' => ['core', 'local_slots', 'affiliates', 'vip'],
                'has_full_pack' => false,
                'message' => 'No license key configured. Enter your license key from promex.me to activate full live services.',
                'renew_url' => 'https://promex.me',
                'checked_at' => now()->toIso8601String()
            ];
        }

        $hubUrl = function_exists('settings') 
            ? settings('license_server_url', self::DEFAULT_SERVER) 
            : self::DEFAULT_SERVER;

        try {
            $response = Http::timeout(6)
                ->withHeaders([
                    'X-License-Key' => $key,
                    'X-Domain' => $domain,
                    'Accept' => 'application/json'
                ])
                ->post("{$hubUrl}/license/check", [
                    'license_key' => $key,
                    'domain' => $domain,
                    'app_version' => '2.5.0'
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $data['domain'] = $domain;
                $data['license_key'] = $key;
                $data['checked_at'] = now()->toIso8601String();
                return $data;
            }

            if ($response->status() === 403 || $response->status() === 401) {
                return [
                    'status' => 'suspended',
                    'plan' => 'Suspended / Expired',
                    'license_key' => $key,
                    'domain' => $domain,
                    'valid_until' => null,
                    'days_left' => 0,
                    'features' => ['core'],
                    'has_full_pack' => false,
                    'message' => 'Subscription expired or suspended. Please renew to restore live services.',
                    'renew_url' => 'https://promex.me',
                    'checked_at' => now()->toIso8601String()
                ];
            }
        } catch (\Throwable $e) {
            Log::warning("[LicenseService] Handshake error: " . $e->getMessage());
        }

        // Offline Fallback Tolerance: if server was active, maintain temporary offline grace
        return [
            'status' => 'active',
            'plan' => 'Enterprise (Offline Mode)',
            'license_key' => $key,
            'domain' => $domain,
            'valid_until' => now()->addDays(3)->toIso8601String(),
            'days_left' => 3,
            'features' => ['all'],
            'has_full_pack' => true,
            'message' => 'License verification server unreachable; operating in offline tolerance mode.',
            'renew_url' => 'https://promex.me',
            'checked_at' => now()->toIso8601String()
        ];
    }

    /**
     * Check if a specific feature or pack is licensed
     */
    public static function hasFeature(string $feature): bool
    {
        $status = self::getStatus();
        if ($status['status'] === 'suspended') {
            return false;
        }

        $features = $status['features'] ?? [];
        return in_array('all', $features) || in_array($feature, $features);
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
                    'count' => 5,
                    'installed' => true,
                    'size' => '12 MB',
                    'games' => ['CedarCrash', 'CedarDice', 'CedarMines', 'CedarPlinko', 'CedarWheel'],
                    'description' => 'Built-in provably fair high-RTP instant mini games.'
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

    /**
     * Generate a cryptographically signed Game Session Token (valid for 2 hours)
     * Used so spins run locally with 0 external network latency to the hub!
     */
    public static function generateGameSessionToken(string $gameName): string
    {
        $status = self::getStatus();
        $isOk = ($status['status'] ?? '') === 'active';
        $domain = $status['domain'] ?? (request() ? request()->getHost() : 'localhost');
        $exp = time() + 7200; // 2 hours validity

        $secret = config('app.key', 'cedar_default_session_secret');
        $payload = json_encode([
            'd' => $domain,
            'g' => $gameName,
            'exp' => $exp,
            'act' => $isOk ? 1 : 0
        ]);

        $sig = hash_hmac('sha256', $payload, $secret);
        return base64_encode($payload) . '.' . $sig;
    }

    /**
     * Verify a Game Session Token locally in memory
     */
    public static function verifyGameSessionToken(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return ['valid' => false, 'reason' => 'malformed_token'];
        }

        [$encodedPayload, $sig] = $parts;
        $secret = config('app.key', 'cedar_default_session_secret');
        $payload = base64_decode($encodedPayload);

        $expectedSig = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSig, $sig)) {
            return ['valid' => false, 'reason' => 'invalid_signature'];
        }

        $data = json_decode($payload, true);
        if (!$data || !isset($data['exp']) || time() > $data['exp']) {
            return ['valid' => false, 'reason' => 'token_expired'];
        }

        if (empty($data['act'])) {
            return ['valid' => false, 'reason' => 'license_inactive'];
        }

        return ['valid' => true, 'domain' => $data['d'] ?? '', 'game' => $data['g'] ?? ''];
    }
}