<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Sports\Category;
use VanguardLTE\Sports\League;
use VanguardLTE\Sports\Game;
use VanguardLTE\Sports\Market;
use VanguardLTE\Sports\Outcome;
use VanguardLTE\Sports\Team;

class SportsControlController extends Controller
{
    public function categories()
    {
        $categories = Category::with('leagues')->orderBy('name')->get();
        return view('liteback.sports.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:40|unique:sports_categories,name',
            'odds_api_name' => 'nullable|string|max:40',
            'regions' => 'nullable|array',
        ]);

        Category::create([
            'name' => $request->input('name'),
            'odds_api_name' => $request->input('odds_api_name'),
            'slug' => Str::slug($request->input('name')),
            'regions' => $request->input('regions', []),
            'status' => 1,
        ]);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function toggleCategory(Category $category)
    {
        $category->status = $category->status ? 0 : 1;
        $category->save();

        return redirect()->back()->with('success', 'Category status updated.');
    }

    public function toggleLeague(League $league, Request $request)
    {
        $league->status = $league->status ? 0 : 1;
        $league->save();

        $msg = 'League status updated.';

        if ($league->status && $request->input('sync') == '1') {
            try {
                $syncService = resolve(\VanguardLTE\Sports\Services\SportsOddsSyncService::class);
                
                // 1. Sync games for this specific league
                $syncService->syncGames($league);
                
                // 2. Sync odds for this specific league
                $syncService->syncOdds('active', $league);
                
                // 3. Open games for betting
                \Illuminate\Support\Facades\Artisan::call('sports:games:open');
                
                $msg .= ' Auto-sync completed successfully. Matches and odds are now live!';
            } catch (\Exception $e) {
                return redirect()->back()->withErrors('League status updated, but auto-sync failed: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', $msg);
    }

    public function toggleLeagueApi(League $league)
    {
        $league->api_status = $league->api_status ? 0 : 1;
        $league->save();

        return redirect()->back()->with('success', 'League API status updated.');
    }

    public function games(Request $request)
    {
        $query = Game::with(['league.category', 'teamOne', 'teamTwo']);

        if ($term = trim($request->input('q', ''))) {
            $query->where('title', 'like', "%{$term}%");
        }

        $games = $query->orderBy('start_time', 'desc')->paginate(20);
        $leagues = League::active()->get();

        return view('liteback.sports.games', compact('games', 'leagues', 'term'));
    }

    public function storeGame(Request $request)
    {
        $request->validate([
            'league_id' => 'required|exists:sports_leagues,id',
            'title' => 'required|string|max:255',
            'team_one' => 'required|string|max:100',
            'team_two' => 'required|string|max:100',
            'start_time' => 'required|date',
            'bet_start_time' => 'required|date',
        ]);

        $league = League::find($request->input('league_id'));

        $teamOne = Team::firstOrCreate([
            'category_id' => $league->category_id,
            'name' => $request->input('team_one'),
        ], ['slug' => Str::slug($request->input('team_one'))]);

        $teamTwo = Team::firstOrCreate([
            'category_id' => $league->category_id,
            'name' => $request->input('team_two'),
        ], ['slug' => Str::slug($request->input('team_two'))]);

        $game = Game::create([
            'league_id' => $league->id,
            'title' => $request->input('title'),
            'team_one_id' => $teamOne->id,
            'team_two_id' => $teamTwo->id,
            'start_time' => $request->input('start_time'),
            'bet_start_time' => $request->input('bet_start_time'),
            'slug' => Str::slug($request->input('title')) . '-' . rand(100, 999),
            'status' => 1,
            'manually_added' => 1,
        ]);

        $game->teams()->sync([$teamOne->id, $teamTwo->id]);

        $market = Market::create([
            'game_id' => $game->id,
            'market_type' => 'h2h',
            'outcome_type' => 1,
            'title' => 'Head to Head',
            'status' => 1,
        ]);

        Outcome::create([
            'market_id' => $market->id,
            'name' => $teamOne->name,
            'odds' => 1.80,
        ]);

        Outcome::create([
            'market_id' => $market->id,
            'name' => $teamTwo->name,
            'odds' => 1.80,
        ]);

        return redirect()->back()->with('success', 'Game created successfully.');
    }

    public function toggleGame(Game $game)
    {
        $game->status = $game->status === 1 ? 0 : 1;
        $game->save();

        return redirect()->back()->with('success', 'Game status updated.');
    }

    public function settings()
    {
        return view('liteback.sports.settings');
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'sports_feature_betting_enabled' => 'required|in:0,1',
            'sports_feature_manual_games' => 'required|in:0,1',
            'sports_feature_manual_odds_override' => 'required|in:0,1',
            'sports_feature_exports_enabled' => 'required|in:0,1',
            'sports_feature_admin_sync_enabled' => 'required|in:0,1',
            'sports_feature_admin_settlement_enabled' => 'required|in:0,1',
            'ods_api_key' => 'nullable|string',
            'ods_api_regions' => 'required|string',
            'ods_api_markets' => 'required|string',
            'single_bet_min_limit' => 'required|numeric|min:0.01',
            'single_bet_max_limit' => 'required|numeric|gt:single_bet_min_limit',
            'multi_bet_min_limit' => 'required|numeric|min:0.01',
            'multi_bet_max_limit' => 'required|numeric|gt:multi_bet_min_limit',
        ]);

        $settings = $request->only([
            'sports_feature_betting_enabled',
            'sports_feature_manual_games',
            'sports_feature_manual_odds_override',
            'sports_feature_exports_enabled',
            'sports_feature_admin_sync_enabled',
            'sports_feature_admin_settlement_enabled',
            'ods_api_key',
            'ods_api_regions',
            'ods_api_markets',
            'single_bet_min_limit',
            'single_bet_max_limit',
            'multi_bet_min_limit',
            'multi_bet_max_limit',
        ]);

        foreach ($settings as $key => $val) {
            settings()->set($key, $val);
        }
        settings()->save();

        return redirect()->back()->with('success', 'Sportsbook settings updated.');
    }
}
