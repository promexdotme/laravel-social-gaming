<?php

namespace VanguardLTE\Sports\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use VanguardLTE\Sports\Category;
use VanguardLTE\Sports\League;
use VanguardLTE\Sports\Team;
use VanguardLTE\Sports\Game;
use VanguardLTE\Sports\Market;
use VanguardLTE\Sports\Outcome;

class SportsOddsSyncService
{
    protected function getProvider(): string
    {
        $provider = env('SPORTSBOOK_PROVIDER');
        if (!empty($provider)) {
            return $provider;
        }
        return settings('sportsbook_provider', 'the_odds_api');
    }

    protected function getBaseUri(): string
    {
        if ($this->getProvider() === 'parlay_api') {
            $url = env('PARLAY_BASE_URL');
            if (empty($url)) {
                $url = settings('parlay_base_url', 'https://parlay-api.com');
            }
            $url = rtrim($url, '/');
            if (strpos($url, '/v1') !== false) {
                return $url . '/';
            }
            return $url . '/v1/';
        }
        return 'https://api.the-odds-api.com/v4/';
    }

    /**
     * Get the API key.
     */
    protected function getApiKey(): string
    {
        if ($this->getProvider() === 'parlay_api') {
            $key = trim(env('PARLAY_API_KEY', ''));
            if (empty($key)) {
                $key = trim(settings('parlay_api_key', ''));
            }
            if (!$key) {
                throw new \Exception("Parlay API Key not set.");
            }
            return $key;
        }

        $key = trim(env('ODS_API_KEY', ''));
        if (empty($key)) {
            $key = trim(settings('ods_api_key', ''));
        }
        if (!$key) {
            throw new \Exception("Odds API key not set.");
        }
        return $key;
    }

    /**
     * Fetch sports list and adjust leagues.
     */
    public function syncSports(): void
    {
        $apiKey = $this->getApiKey();
        $baseUri = $this->getBaseUri();
        $url = "{$baseUri}sports?apiKey={$apiKey}";
        if ($this->getProvider() === 'the_odds_api') {
            $url .= "&all=true";
        }
        $response = Http::get($url);

        if ($response->failed()) {
            throw new \Exception("Odds API request failed: " . $response->body());
        }

        $sports = $response->json();

        DB::transaction(function () use ($sports) {
            $categories = Category::all();
            $newLeagues = [];

            foreach ($sports as $sport) {
                $sport = (object)$sport;

                $category = $categories->firstWhere('odds_api_name', $sport->group);
                if (!$category) {
                    continue;
                }

                $league = League::whereNull('odds_api_sport_key')
                    ->where('name', $sport->title)
                    ->where('category_id', $category->id)
                    ->first();

                if ($league) {
                    $league->odds_api_sport_key = $sport->key;
                    $league->api_status = ($sport->active ?? false) ? 1 : 0;
                    $league->save();
                } else {
                    $exists = League::where('odds_api_sport_key', $sport->key)->exists();
                    if (!$exists) {
                        $slug = Str::slug($sport->key);
                        if (League::where('slug', $slug)->exists()) {
                            $slug .= '-' . rand(100, 999);
                        }

                        $newLeagues[] = [
                            'odds_api_sport_key' => $sport->key,
                            'category_id'        => $category->id,
                            'name'               => $sport->title,
                            'short_name'         => $sport->title,
                            'slug'               => $slug,
                            'description'        => $sport->description,
                            'has_outrights'      => ($sport->has_outrights ?? false) ? 1 : 0,
                            'api_status'         => ($sport->active ?? false) ? 1 : 0,
                            'status'             => 0, // Disabled by default
                            'manually_added'     => 0,
                            'created_at'         => now(),
                            'updated_at'         => now(),
                        ];
                    }
                }
            }

            if (!empty($newLeagues)) {
                League::insert($newLeagues);
            }
        });
    }

    /**
     * Fetch games/events for active running leagues.
     */
    public function syncGames(?League $targetLeague = null): void
    {
        $apiKey = $this->getApiKey();
        $baseUri = $this->getBaseUri();
        $leagues = $targetLeague ? collect([$targetLeague]) : League::running()->whereNotNull('odds_api_sport_key')->get();

        foreach ($leagues as $league) {
            $response = Http::get("{$baseUri}sports/{$league->odds_api_sport_key}/events?apiKey={$apiKey}");

            if ($response->failed()) {
                Log::error("Failed fetching games for league {$league->odds_api_sport_key}: " . $response->body());
                continue;
            }

            $events = $response->json();
            if (empty($events)) {
                continue;
            }

            foreach ($events as $event) {
                $event = (object)$event;

                DB::transaction(function () use ($league, $event) {
                    $homeTeam = !empty($event->home_team) ? $this->saveTeam($league, $event->home_team) : null;
                    $awayTeam = !empty($event->away_team) ? $this->saveTeam($league, $event->away_team) : null;

                    $game = Game::where('ods_api_id', $event->id)->first();

                    if (!$game) {
                        $this->saveGame($league, $event, $homeTeam, $awayTeam);
                    } else {
                        $game->start_time = $this->parseCommenceTime($event->commence_time)->format('Y-m-d H:i:s');
                        $game->save();
                    }
                });
            }
        }
    }

    /**
     * Fetch odds and save markets/outcomes.
     */
    public function syncOdds(string $type = 'active', ?League $targetLeague = null): void
    {
        $apiKey = $this->getApiKey();
        $baseUri = $this->getBaseUri();
        $provider = $this->getProvider();
        if ($targetLeague) {
            $leagues = collect([$targetLeague]);
        } else {
            $leaguesQuery = League::running()->whereNotNull('odds_api_sport_key');

            if ($type === 'running') {
                $leaguesQuery->whereHas('runningActiveGames');
            }

            $leagues = $leaguesQuery->get();
        }

        $regionsSetting = settings('ods_api_regions', 'us');
        $regions = is_array($regionsSetting) ? implode(',', $regionsSetting) : $regionsSetting;

        $marketsSetting = settings('ods_api_markets', 'h2h');
        $marketsArr = is_array($marketsSetting) ? $marketsSetting : explode(',', $marketsSetting);

        foreach ($leagues as $league) {
            $leagueMarkets = $marketsArr;
            if (!$league->has_outrights) {
                $outrightsKey = array_search('outrights', $leagueMarkets);
                if ($outrightsKey !== false) {
                    unset($leagueMarkets[$outrightsKey]);
                }
            }

            $marketsStr = implode(',', $leagueMarkets);
            $url = "{$baseUri}sports/{$league->odds_api_sport_key}/odds?apiKey={$apiKey}&regions={$regions}&markets={$marketsStr}";
            if ($provider === 'parlay_api') {
                $url .= "&bookmakers=pinnacle";
            } else {
                $url = "{$baseUri}sports/{$league->odds_api_sport_key}/odds/?apiKey={$apiKey}&regions={$regions}&markets={$marketsStr}";
            }

            $response = Http::get($url);

            if ($response->failed()) {
                Log::error("Failed fetching odds for league {$league->odds_api_sport_key}: " . $response->body());
                continue;
            }

            $events = $response->json();
            if (empty($events)) {
                continue;
            }

            foreach ($events as $event) {
                $event = (object)$event;

                DB::transaction(function () use ($league, $event, $leagueMarkets) {
                    $homeTeam = !empty($event->home_team) ? $this->saveTeam($league, $event->home_team) : null;
                    $awayTeam = !empty($event->away_team) ? $this->saveTeam($league, $event->away_team) : null;

                    if (empty($event->bookmakers)) {
                        return;
                    }

                    $game = Game::where('ods_api_id', $event->id)->first();
                    if (!$game) {
                        $game = $this->saveGame($league, $event, $homeTeam, $awayTeam);
                    } else {
                        $game->start_time = $this->parseCommenceTime($event->commence_time)->format('Y-m-d H:i:s');
                        $game->save();
                    }

                    $extractedMarkets = [];
                    foreach ($leagueMarkets as $mKey) {
                        $marketData = collect($event->bookmakers)
                            ->pluck('markets')
                            ->flatten(1)
                            ->where('key', $mKey)
                            ->sortByDesc('last_update')
                            ->first();

                        if (!$marketData) {
                            continue;
                        }

                        $marketData = (object)$marketData;

                        if ($marketData->key === 'h2h') {
                            $drawOutcome = collect($marketData->outcomes)->where('name', 'Draw')->first();
                            if ($drawOutcome) {
                                $newMarket = clone $marketData;
                                $newMarket->key = 'h2h_3way';
                                $extractedMarkets[] = $newMarket;
                            } else {
                                $extractedMarkets[] = $marketData;
                            }
                        } else {
                            $extractedMarkets[] = $marketData;
                        }
                    }

                    foreach ($extractedMarkets as $mExtracted) {
                        $this->saveMarketAndOutcomes($league, $game, $mExtracted);
                    }
                });
            }
        }
    }

    protected function saveTeam(League $league, string $teamName): Team
    {
        $team = Team::where('category_id', $league->category_id)->where('name', $teamName)->first();
        if (!$team) {
            $slug = Str::slug($teamName);
            if (Team::where('slug', $slug)->exists()) {
                $slug .= '-' . rand(100, 999);
            }
            $team = Team::create([
                'name' => $teamName,
                'short_name' => $teamName,
                'category_id' => $league->category_id,
                'manually_added' => 0,
                'slug' => $slug,
            ]);
        }
        return $team;
    }

    protected function saveGame(League $league, $event, ?Team $homeTeam, ?Team $awayTeam): Game
    {
        $title = ($homeTeam && $awayTeam) ? "{$homeTeam->name} vs {$awayTeam->name}" : $league->name;
        $slug = Str::slug($title);
        if (Game::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(100, 999);
        }

        $game = Game::create([
            'ods_api_id' => $event->id,
            'team_one_id' => $homeTeam->id ?? 0,
            'team_two_id' => $awayTeam->id ?? 0,
            'league_id' => $league->id,
            'title' => $title,
            'slug' => $slug,
            'bet_start_time' => now(),
            'start_time' => $this->parseCommenceTime($event->commence_time)->format('Y-m-d H:i:s'),
            'is_outright' => $league->has_outrights ?? 0,
            'manually_added' => 0,
            'status' => 1,
        ]);

        $teamsToSync = [];
        if ($homeTeam) $teamsToSync[] = $homeTeam->id;
        if ($awayTeam) $teamsToSync[] = $awayTeam->id;
        if (!empty($teamsToSync)) {
            $game->teams()->syncWithoutDetaching($teamsToSync);
        }

        return $game;
    }

    protected function saveMarketAndOutcomes(League $league, Game $game, $marketData, string $oddsFormat = 'decimal'): void
    {
        $title = $marketData->key;
        $outcomeType = 1;

        if ($marketData->key === 'h2h') {
            $title = 'Head to Head';
        } elseif ($marketData->key === 'h2h_3way') {
            $title = 'Head to Head 3 Way';
        } elseif ($marketData->key === 'spreads') {
            $title = 'Spreads';
            $outcomeType = 2;
        } elseif ($marketData->key === 'totals') {
            $title = 'Totals';
            $outcomeType = 3;
        }

        $market = Market::updateOrCreate(
            ['game_id' => $game->id, 'market_type' => $marketData->key],
            [
                'outcome_type' => $outcomeType,
                'title' => $title,
                'status' => 1,
                'market_updated_at' => Carbon::parse($marketData->last_update ?? now())->format('Y-m-d H:i:s'),
            ]
        );

        $teams = [];
        foreach ($marketData->outcomes as $outcome) {
            $outcome = (object)$outcome;

            if (!empty($outcome->name) && $game->team_one_id === 0 && $game->team_two_id === 0) {
                $team = Team::firstOrCreate(
                    ['name' => $outcome->name, 'category_id' => $league->category_id],
                    [
                        'short_name' => $outcome->name,
                        'slug' => Str::slug($outcome->name) . '-' . rand(100, 999),
                        'manually_added' => 0,
                    ]
                );
                $teams[] = $team->id;
            }

            $price = (float)$outcome->price;
            if ($oddsFormat === 'american') {
                $price = $this->americanToDecimal($price);
            }

            Outcome::updateOrCreate(
                ['market_id' => $market->id, 'name' => $outcome->name],
                [
                    'odds' => $price,
                    'point' => isset($outcome->point) ? (float)$outcome->point : null,
                    'status' => 1,
                ]
            );
        }

        if (!empty($teams)) {
            $game->teams()->syncWithoutDetaching($teams);
        }
    }

    /**
     * Fetch upcoming odds globally across active categories.
     */
    public function syncUpcomingOdds(): void
    {
        $apiKey = $this->getApiKey();
        $baseUri = $this->getBaseUri();
        $provider = $this->getProvider();
        if ($provider === 'parlay_api') {
            $this->syncOdds('active');
            return;
        }
        $regionsSetting = settings('ods_api_regions', 'us');
        $regions = is_array($regionsSetting) ? implode(',', $regionsSetting) : $regionsSetting;

        $marketsSetting = settings('ods_api_markets', 'h2h');
        $marketsStr = is_array($marketsSetting) ? implode(',', $marketsSetting) : $marketsSetting;

        $url = "{$baseUri}sports/upcoming/odds?apiKey={$apiKey}&regions={$regions}&markets={$marketsStr}&oddsFormat=american";
        if ($provider === 'parlay_api') {
            $url .= "&bookmakers=pinnacle";
        } else {
            $url = "{$baseUri}sports/upcoming/odds/?apiKey={$apiKey}&regions={$regions}&markets={$marketsStr}&oddsFormat=american";
        }

        $response = Http::get($url);

        if ($response->failed()) {
            throw new \Exception("Odds API request failed: " . $response->body());
        }

        $events = $response->json();
        if (empty($events)) {
            return;
        }

        foreach ($events as $event) {
            $event = (object)$event;

            $league = $this->resolveLeague($event->sport_key, $event->sport_title);
            if (!$league) {
                continue;
            }

            DB::transaction(function () use ($league, $event, $marketsStr) {
                $homeTeam = !empty($event->home_team) ? $this->saveTeam($league, $event->home_team) : null;
                $awayTeam = !empty($event->away_team) ? $this->saveTeam($league, $event->away_team) : null;

                if (empty($event->bookmakers)) {
                    return;
                }

                $game = Game::where('ods_api_id', $event->id)->first();
                if (!$game) {
                    $game = $this->saveGame($league, $event, $homeTeam, $awayTeam);
                } else {
                    $game->start_time = $this->parseCommenceTime($event->commence_time)->format('Y-m-d H:i:s');
                    $game->save();
                }

                $marketsArr = explode(',', $marketsStr);
                $extractedMarkets = [];
                foreach ($marketsArr as $mKey) {
                    $marketData = collect($event->bookmakers)
                        ->pluck('markets')
                        ->flatten(1)
                        ->where('key', $mKey)
                        ->sortByDesc('last_update')
                        ->first();

                    if (!$marketData) {
                        continue;
                    }

                    $marketData = (object)$marketData;

                    if ($marketData->key === 'h2h') {
                        $drawOutcome = collect($marketData->outcomes)->where('name', 'Draw')->first();
                        if ($drawOutcome) {
                            $newMarket = clone $marketData;
                            $newMarket->key = 'h2h_3way';
                            $extractedMarkets[] = $newMarket;
                        } else {
                            $extractedMarkets[] = $marketData;
                        }
                    } else {
                        $extractedMarkets[] = $marketData;
                    }
                }

                foreach ($extractedMarkets as $mExtracted) {
                    $this->saveMarketAndOutcomes($league, $game, $mExtracted, 'american');
                }
            });
        }
    }

    /**
     * Resolve a league by its odds_api_sport_key, creating it if it doesn't exist.
     */
    protected function resolveLeague(string $sportKey, string $sportTitle): ?League
    {
        $league = League::where('odds_api_sport_key', $sportKey)->first();
        if ($league) {
            return $league;
        }

        $groupName = '';
        if (strpos($sportKey, 'soccer') === 0) {
            $groupName = 'Soccer';
        } elseif (strpos($sportKey, 'basketball') === 0) {
            $groupName = 'Basketball';
        } elseif (strpos($sportKey, 'americanfootball') === 0) {
            $groupName = 'American Football';
        } elseif (strpos($sportKey, 'icehockey') === 0) {
            $groupName = 'Ice Hockey';
        } elseif (strpos($sportKey, 'tennis') === 0) {
            $groupName = 'Tennis';
        } elseif (strpos($sportKey, 'cricket') === 0) {
            $groupName = 'Cricket';
        } elseif (strpos($sportKey, 'baseball') === 0) {
            $groupName = 'Baseball';
        }

        if (!$groupName) {
            $groupName = ucfirst(explode('_', $sportKey)[0]);
        }

        $category = Category::where('name', $groupName)->first();
        if (!$category) {
            $category = Category::create([
                'name' => $groupName,
                'odds_api_name' => $groupName,
                'slug' => Str::slug($groupName),
                'status' => 1,
            ]);
        }

        $slug = Str::slug($sportKey);
        if (League::where('slug', $slug)->exists()) {
            $slug .= '-' . rand(100, 999);
        }

        return League::create([
            'odds_api_sport_key' => $sportKey,
            'category_id'        => $category->id,
            'name'               => $sportTitle,
            'short_name'         => $sportTitle,
            'slug'               => $slug,
            'has_outrights'      => 0,
            'api_status'         => 1,
            'status'             => 1,
            'manually_added'     => 0,
        ]);
    }

    /**
     * Convert American odds to Decimal.
     */
    protected function americanToDecimal(float $american): float
    {
        if ($american > 0) {
            return round(($american / 100) + 1, 2);
        } elseif ($american < 0) {
            return round((100 / abs($american)) + 1, 2);
        }
        return 1.0;
    }

    /**
     * Parse the commence time and shift it if in sandbox mode.
     */
    protected function parseCommenceTime(string $timeStr): Carbon
    {
        $commenceTime = Carbon::parse($timeStr);
        $baseUri = $this->getBaseUri();

        if (strpos($baseUri, 'sandbox') !== false) {
            // Shift June 2nd sandbox date to current month/day
            $sandboxBase = Carbon::parse('2026-06-02 00:00:00');
            $daysDiff = $sandboxBase->diffInDays(Carbon::today(), false);
            $shiftedTime = $commenceTime->copy()->addDays($daysDiff);
            if ($shiftedTime->isPast()) {
                $shiftedTime->addDays(2);
            }
            $commenceTime = $shiftedTime;
        }

        return $commenceTime->timezone(config('app.timezone', 'Europe/Berlin'));
    }
}

