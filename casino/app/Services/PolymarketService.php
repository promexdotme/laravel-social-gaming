<?php

namespace VanguardLTE\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PolymarketService
{
    /**
     * Get the Polymarket Gamma API Endpoint
     */
    public function getEndpoint(): string
    {
        if (function_exists('settings')) {
            $url = settings('polymarket_api_url');
            if (!empty($url)) {
                return $url;
            }
        }
        return 'https://gamma-api.polymarket.com/events';
    }

    /**
     * Search and retrieve live prediction markets via official Polymarket Gamma API
     */
    public function searchEvents(string $keyword = ''): array
    {
        try {
            $rawEvents = $this->fetchTopEvents(100);
            if (!empty($rawEvents)) {
                $formatted = $this->formatEventsPayload($rawEvents, $keyword);
                if (!empty($formatted)) {
                    return $formatted;
                }
            }
        } catch (\Throwable $e) {
            Log::warning("[Polymarket Gamma Search Error] " . $e->getMessage());
        }

        return $this->getMockSearchResults($keyword);
    }

    /**
     * Fetch top volume events from Polymarket Gamma API with 60-second caching
     */
    public function fetchTopEvents(int $limit = 100): array
    {
        return Cache::remember('polymarket_gamma_top_events_v2', 60, function () use ($limit) {
            try {
                $endpoint = $this->getEndpoint();
                $response = Http::timeout(8)
                    ->withHeaders([
                        'User-Agent' => 'CasinoDuLiban/SocialGaming/2.0',
                        'Accept' => 'application/json',
                    ])
                    ->get($endpoint, [
                        'limit' => $limit,
                        'active' => 'true',
                        'closed' => 'false',
                        'order' => 'volume24hr',
                        'ascending' => 'false'
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json)) {
                        return $json;
                    }
                } else {
                    Log::warning("[Polymarket Gamma HTTP Error] Status: " . $response->status());
                }
            } catch (\Throwable $e) {
                Log::warning("[Polymarket Gamma Exception] " . $e->getMessage());
            }

            return [];
        });
    }

    /**
     * Format Polymarket raw JSON array with sub-market expansion, keyword filtering, and precise probabilities
     */
    protected function formatEventsPayload(array $rawEvents, string $keyword = ''): array
    {
        $formatted = [];
        $kw = strtolower(trim($keyword));

        foreach ($rawEvents as $event) {
            $eventId = $event['id'] ?? null;
            if (!$eventId) continue;

            $eventTitle = trim($event['title'] ?? 'Untitled Market');
            $eventDesc = trim($event['description'] ?? '');
            $eventEndDate = $event['endDate'] ?? now()->addDays(30)->toIso8601String();

            // Derive intelligent category from tags and title
            $tagsStr = '';
            if (!empty($event['tags']) && is_array($event['tags'])) {
                $labels = array_column($event['tags'], 'label');
                $tagsStr = implode(' ', $labels);
            }
            $category = $this->detectCategory($eventTitle . ' ' . $tagsStr);

            $markets = $event['markets'] ?? [];

            // If event has sub-markets, expand them to get actual specific questions & real odds
            if (!empty($markets) && is_array($markets)) {
                foreach ($markets as $m) {
                    $mId = $m['id'] ?? null;
                    if (!$mId) continue;

                    $question = trim($m['question'] ?? '');
                    $groupTitle = trim($m['groupItemTitle'] ?? '');
                    $mDesc = trim($m['description'] ?? $eventDesc);

                    // Build clean display title
                    $displayTitle = !empty($question) ? $question : $eventTitle;
                    if (!empty($groupTitle) && ($displayTitle === $eventTitle || empty($question))) {
                        $displayTitle = "{$eventTitle} ({$groupTitle})";
                    }

                    // Keyword filter across title, question, group, description, and tags
                    if (!empty($kw)) {
                        $searchText = strtolower($displayTitle . ' ' . $mDesc . ' ' . $groupTitle . ' ' . $tagsStr);
                        if (!str_contains($searchText, $kw)) {
                            continue;
                        }
                    }

                    // Parse real outcome prices
                    [$yesPrice, $noPrice] = $this->parseOutcomePrices($m['outcomePrices'] ?? null);

                    // Skip markets with zero liquidity or already resolved (0 or 1)
                    if ($yesPrice <= 0.005 || $yesPrice >= 0.995) {
                        continue;
                    }

                    $yesPercent = (int) round($yesPrice * 100);
                    $noPercent = 100 - $yesPercent;
                    $yesOdds = round(1 / max(0.01, $yesPrice), 2);
                    $noOdds = round(1 / max(0.01, $noPrice), 2);

                    $formatted[] = [
                        'market_id' => 'poly_m_' . $mId,
                        'title' => $displayTitle,
                        'description' => $mDesc ?: "Polymarket: {$displayTitle}",
                        'category' => $category,
                        'yes_price' => $yesPrice,
                        'no_price' => $noPrice,
                        'yes_percent' => $yesPercent,
                        'no_percent' => $noPercent,
                        'yes_odds' => max(1.05, min(20.00, $yesOdds)),
                        'no_odds' => max(1.05, min(20.00, $noOdds)),
                        'total_volume' => (float)($m['volume'] ?? $m['volume24hr'] ?? 5000),
                        'end_date' => $m['endDate'] ?? $eventEndDate,
                        'is_local' => false,
                    ];
                }
            } else {
                // Single event fallback
                if (!empty($kw)) {
                    $searchText = strtolower($eventTitle . ' ' . $eventDesc . ' ' . $tagsStr);
                    if (!str_contains($searchText, $kw)) {
                        continue;
                    }
                }

                $formatted[] = [
                    'market_id' => 'poly_e_' . $eventId,
                    'title' => $eventTitle,
                    'description' => $eventDesc ?: "Polymarket: {$eventTitle}",
                    'category' => $category,
                    'yes_price' => 0.50,
                    'no_price' => 0.50,
                    'yes_percent' => 50,
                    'no_percent' => 50,
                    'yes_odds' => 2.00,
                    'no_odds' => 2.00,
                    'total_volume' => 5000,
                    'end_date' => $eventEndDate,
                    'is_local' => false,
                ];
            }
        }

        return $formatted;
    }

    /**
     * Safely parse outcomePrices JSON string or array
     */
    protected function parseOutcomePrices($raw): array
    {
        if (empty($raw)) {
            return [0.50, 0.50];
        }

        $prices = is_string($raw) ? json_decode($raw, true) : $raw;

        if (is_array($prices) && count($prices) >= 2) {
            $p0 = (float)($prices[0] ?? 0.50);
            $p1 = (float)($prices[1] ?? 0.50);

            if ($p0 >= 0 && $p0 <= 1) {
                $p0 = round($p0, 2);
                $p1 = round(1.00 - $p0, 2);
                return [$p0, $p1];
            }
        }

        return [0.50, 0.50];
    }

    /**
     * Intelligent category mapping
     */
    protected function detectCategory(string $text): string
    {
        $text = strtolower($text);
        if (preg_match('/(btc|eth|bitcoin|ethereum|crypto|solana|coin|nft|token)/', $text)) {
            return 'crypto';
        }
        if (preg_match('/(president|election|trump|biden|kamala|senate|congress|war|ukraine|iran|china|minister|politics|government)/', $text)) {
            return 'geopolitics';
        }
        if (preg_match('/(uefa|champions league|premier league|nba|nfl|soccer|football|tennis|cup|match|cs:go|counter-strike|esport|lol)/', $text)) {
            return 'sports';
        }
        if (preg_match('/(ai|spacex|mars|openai|robot|tech|apple|nvidia|tesla|google)/', $text)) {
            return 'tech';
        }
        return 'popculture';
    }

    /**
     * Fallback mock results if offline or unpopulated
     */
    protected function getMockSearchResults(string $keyword): array
    {
        $allMocks = [
            [
                'market_id' => 'poly_mock_101',
                'title' => 'Will AI Humanoid Robots achieve 10,000 home sales before 2027?',
                'description' => 'Resolves YES if at least one commercial humanoid achieves 10,000 consumer sales before Dec 31, 2026.',
                'category' => 'tech',
                'yes_price' => 0.42,
                'no_price' => 0.58,
                'yes_percent' => 42,
                'no_percent' => 58,
                'yes_odds' => 2.38,
                'no_odds' => 1.72,
                'total_volume' => 12500,
                'end_date' => now()->addMonths(6)->toIso8601String(),
                'is_local' => false,
            ],
            [
                'market_id' => 'poly_mock_102',
                'title' => 'Will SpaceX Starship land uncrewed payload on Mars before 2027?',
                'description' => 'Resolves YES if Starship lands on Mars surface before Jan 1, 2027.',
                'category' => 'tech',
                'yes_price' => 0.64,
                'no_price' => 0.36,
                'yes_percent' => 64,
                'no_percent' => 36,
                'yes_odds' => 1.56,
                'no_odds' => 2.75,
                'total_volume' => 35400,
                'end_date' => now()->addMonths(8)->toIso8601String(),
                'is_local' => false,
            ]
        ];

        if (empty($keyword)) {
            return $allMocks;
        }

        $kw = strtolower($keyword);
        $filtered = [];

        foreach ($allMocks as $m) {
            if (str_contains(strtolower($m['title'] . ' ' . $m['description']), $kw)) {
                $filtered[] = $m;
            }
        }

        return $filtered;
    }
}