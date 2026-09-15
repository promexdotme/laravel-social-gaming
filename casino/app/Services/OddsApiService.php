<?php

namespace VanguardLTE\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use VanguardLTE\SportsMatch;

class OddsApiService
{
    protected $apiKey;
    protected $region = 'eu';
    protected $baseUrl = 'https://api.the-odds-api.com/v4/sports';

    public function __construct()
    {
        $cfgKey = function_exists('settings') ? settings('odds_api_key', '') : '';
        $this->apiKey = !empty($cfgKey) ? $cfgKey : env('THE_ODDS_API_KEY', env('ODDS_API_KEY', ''));
        $this->region = function_exists('settings') ? settings('odds_api_region', 'eu') : 'eu';
    }

    /**
     * Fetch upcoming pre-match fixtures for key sports
     */
    public function syncUpcomingFixtures(): array
    {
        $sportsToSync = [
            'soccer_uefa_champs_league' => 'UEFA Champions League',
            'soccer_epl' => 'English Premier League',
            'soccer_spain_la_liga' => 'La Liga',
            'basketball_nba' => 'NBA Basketball',
            'mma_mixed_martial_arts' => 'UFC / MMA'
        ];

        $totalSynced = 0;
        $provider = env('SPORTSBOOK_PROVIDER', 'clients_377');
        $hubUrl = function_exists('settings') ? settings('license_server_url', 'https://clients.377.live/api/service') : 'https://clients.377.live/api/service';

        // 1. Try Central Service Hub (Pre-cached odds via Redis)
        if ($provider === 'clients_377' || empty($this->apiKey)) {
            try {
                $license = LicenseService::getStatus();
                $domain = $license['domain'] ?? request()->getHost();
                $key = $license['license_key'] ?? '';

                $hubResponse = Http::timeout(6)
                    ->withHeaders([
                        'X-License-Key' => $key,
                        'X-Domain' => $domain,
                        'Accept' => 'application/json'
                    ])
                    ->get("{$hubUrl}/sports/odds");

                if ($hubResponse->successful()) {
                    $hubData = $hubResponse->json();
                    if (!empty($hubData['fixtures']) && is_array($hubData['fixtures'])) {
                        foreach ($hubData['fixtures'] as $fix) {
                            $this->saveFixture($fix, $fix['sport_key'] ?? 'soccer_epl', $fix['sport_title'] ?? 'Live Match');
                            $totalSynced++;
                        }
                        if ($totalSynced > 0) {
                            if (function_exists('settings')) {
                                settings(['sports_last_sync' => now()->toDateTimeString()]);
                            }
                            return ['success' => true, 'synced' => $totalSynced, 'provider' => 'clients_377'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("[Sportsbook Hub] Central cache unavailable: " . $e->getMessage());
            }
        }

        // 2. Direct Upstream API (The Odds API) or Local Generator
        foreach ($sportsToSync as $sportKey => $sportTitle) {
            try {
                if (!empty($this->apiKey)) {
                    $response = Http::timeout(8)->get("{$this->baseUrl}/{$sportKey}/odds/", [
                        'apiKey' => $this->apiKey,
                        'regions' => $this->region,
                        'markets' => 'h2h',
                        'oddsFormat' => 'decimal'
                    ]);

                    if ($response->successful()) {
                        $fixtures = $response->json();
                        foreach ($fixtures as $fix) {
                            $this->saveFixture($fix, $sportKey, $sportTitle);
                            $totalSynced++;
                        }
                        continue;
                    }
                }

                // Fallback to offline pre-match generator if API unavailable
                $this->seedMockFixtures($sportKey, $sportTitle);
                $totalSynced += 3;
            } catch (\Exception $e) {
                Log::error("[The Odds API Exception] {$e->getMessage()}");
                $this->seedMockFixtures($sportKey, $sportTitle);
                $totalSynced += 3;
            }
        }

        if (function_exists('settings')) {
            settings()->set('last_odds_sync_at', now()->toDateTimeString());
            settings()->set('last_odds_sync_count', $totalSynced);
            settings()->save();
        }

        return [
            'success' => true,
            'synced_count' => $totalSynced,
            'synced_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Save/Update a single match fixture
     */
    protected function saveFixture(array $fix, string $sportKey, string $sportTitle)
    {
        $matchId = $fix['id'] ?? null;
        if (!$matchId) return;

        $homeTeam = $fix['home_team'] ?? 'Home Team';
        $awayTeam = $fix['away_team'] ?? 'Away Team';
        $startTime = $fix['commence_time'] ?? now()->addHours(2)->toIso8601String();

        $oddsHome = 1.90;
        $oddsDraw = 3.20;
        $oddsAway = 1.90;

        // Parse odds from first available bookmaker (e.g. bet365, williamhill)
        if (!empty($fix['bookmakers'])) {
            $bm = $fix['bookmakers'][0];
            if (!empty($bm['markets'])) {
                foreach ($bm['markets'] as $mkt) {
                    if ($mkt['key'] === 'h2h') {
                        foreach ($mkt['outcomes'] as $out) {
                            if ($out['name'] === $homeTeam) $oddsHome = (float)$out['price'];
                            elseif ($out['name'] === $awayTeam) $oddsAway = (float)$out['price'];
                            elseif (strtolower($out['name']) === 'draw') $oddsDraw = (float)$out['price'];
                        }
                    }
                }
            }
        }

        SportsMatch::updateOrCreate(
            ['match_id' => $matchId],
            [
                'sport_key' => $sportKey,
                'sport_title' => $sportTitle,
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
                'start_time' => \Carbon\Carbon::parse($startTime)->setTimezone('UTC'),
                'odds_home' => $oddsHome,
                'odds_draw' => str_contains($sportKey, 'soccer') ? $oddsDraw : null,
                'odds_away' => $oddsAway,
                'status' => 'upcoming',
            ]
        );
    }

    /**
     * Seed realistic pre-match fixtures if API key is rate-limited or offline
     */
    protected function seedMockFixtures(string $sportKey, string $sportTitle)
    {
        $samples = [
            'soccer_uefa_champs_league' => [
                ['Real Madrid', 'Manchester City', 2.45, 3.40, 2.80],
                ['Bayern Munich', 'Paris Saint-Germain', 2.10, 3.60, 3.20],
                ['FC Barcelona', 'Inter Milan', 1.85, 3.50, 4.00],
            ],
            'soccer_epl' => [
                ['Arsenal', 'Chelsea', 1.95, 3.40, 3.80],
                ['Liverpool', 'Manchester United', 1.75, 3.80, 4.20],
            ],
            'basketball_nba' => [
                ['Boston Celtics', 'Los Angeles Lakers', 1.65, null, 2.25],
                ['Golden State Warriors', 'Denver Nuggets', 1.90, null, 1.90],
            ]
        ];

        $list = $samples[$sportKey] ?? [
            ['Home Stars', 'Away Legends', 1.90, 3.10, 2.00]
        ];

        foreach ($list as $idx => $m) {
            $mId = "mock_" . $sportKey . "_" . ($idx + 1);
            SportsMatch::updateOrCreate(
                ['match_id' => $mId],
                [
                    'sport_key' => $sportKey,
                    'sport_title' => $sportTitle,
                    'home_team' => $m[0],
                    'away_team' => $m[1],
                    'start_time' => now()->addHours($idx + 2)->setTimezone('UTC'),
                    'odds_home' => $m[2],
                    'odds_draw' => $m[3],
                    'odds_away' => $m[4],
                    'status' => 'upcoming',
                ]
            );
        }
    }
}
