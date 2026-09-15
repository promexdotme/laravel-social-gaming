<?php

namespace VanguardLTE\Http\Controllers\Web\Liteback;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use VanguardLTE\Http\Controllers\Controller;

class GameController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->input('per_page', 25);
        $term = trim((string) $request->input('q', ''));
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;
        $status = $request->input('status', 'all'); // all, active, disabled

        // 1. Fetch all providers / categories with game stats using ID + original_id mapping
        $providers = DB::select("
            SELECT 
                c.id, 
                c.title, 
                COUNT(DISTINCT g.id) as total_games,
                SUM(CASE WHEN g.view = 1 THEN 1 ELSE 0 END) as active_games,
                SUM(CASE WHEN g.view = 0 THEN 1 ELSE 0 END) as disabled_games
            FROM w_categories c
            JOIN w_game_categories gc ON c.id = gc.category_id
            JOIN w_games g ON (gc.game_id = g.id OR (g.original_id > 0 AND gc.game_id = g.original_id))
            GROUP BY c.id, c.title
            HAVING total_games > 0
            ORDER BY total_games DESC
        ");

        // 2. Build games query
        $query = DB::table('games')->select(
            'games.id',
            'games.original_id',
            'games.name',
            'games.title',
            'games.view',
            'games.bet',
            'games.denomination',
            'games.bids',
            'games.stat_in',
            'games.stat_out',
            'games.current_rtp',
            'games.shop_id',
            'games.source_type',
            'games.custom_path'
        );

        if ($categoryId) {
            $targetIds = DB::table('game_categories')->where('category_id', $categoryId)->pluck('game_id')->toArray();
            $query->where(function ($q) use ($targetIds) {
                $q->whereIn('games.id', $targetIds)
                    ->orWhere(function ($sub) use ($targetIds) {
                        $sub->where('games.original_id', '>', 0)
                            ->whereIn('games.original_id', $targetIds);
                    });
            });
        }

        if ($status === 'active') {
            $query->where('games.view', 1);
        } elseif ($status === 'disabled') {
            $query->where('games.view', 0);
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('games.title', 'like', '%' . $term . '%')
                    ->orWhere('games.name', 'like', '%' . $term . '%')
                    ->orWhere('games.id', $term);
            });
        }

        $games = $query->orderByDesc('games.id')
            ->paginate($perPage)
            ->appends($request->only(['q', 'category_id', 'status', 'per_page']));

        // 3. Attach category names to each game in current page
        $gameIds = $games->pluck('id')->toArray();
        $gameOrigIds = $games->pluck('original_id')->filter(function ($id) {
            return (int) $id > 0;
        })->toArray();
        $allLookups = array_unique(array_merge($gameIds, $gameOrigIds));

        if (!empty($allLookups)) {
            $gameCats = DB::table('game_categories')
                ->join('categories', 'game_categories.category_id', '=', 'categories.id')
                ->whereIn('game_categories.game_id', $allLookups)
                ->select('game_categories.game_id', 'categories.title')
                ->get();

            foreach ($games as $g) {
                $matchedTitles = $gameCats->filter(function ($row) use ($g) {
                    return (int) $row->game_id === (int) $g->id ||
                        ((int) $g->original_id > 0 && (int) $row->game_id === (int) $g->original_id);
                })->pluck('title')->unique()->values()->toArray();

                $g->category_names = $matchedTitles;
            }
        }

        $totalActive = DB::table('games')->where('view', 1)->count();
        $totalDisabled = DB::table('games')->where('view', 0)->count();

        return view('liteback.games.index', [
            'games' => $games,
            'providers' => $providers,
            'term' => $term,
            'selectedCategory' => $categoryId,
            'selectedStatus' => $status,
            'totalActive' => $totalActive,
            'totalDisabled' => $totalDisabled,
            'inactive' => false,
        ]);
    }

    public function inactive(Request $request)
    {
        return redirect()->route('liteback.games.index', ['status' => 'disabled']);
    }

    /**
     * 1-Click Game Visibility Toggle (view 1 <-> 0)
     */
    public function toggleView(Request $request, $gameId)
    {
        $game = DB::table('games')->where('id', $gameId)->first();
        if (!$game) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Game not found.'], 404);
            }
            return redirect()->back()->withErrors('Game not found.');
        }

        $newView = ((int) $game->view === 1) ? 0 : 1;
        DB::table('games')->where('id', $gameId)->update(['view' => $newView]);

        $statusLabel = $newView === 1 ? 'Activated' : 'Disabled';

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'view' => $newView,
                'message' => "Game '{$game->title}' is now {$statusLabel}.",
            ]);
        }

        return redirect()->back()->with('success', "Game '{$game->title}' is now {$statusLabel}.");
    }

    /**
     * Bulk Provider / Category Killswitch (Enable or Disable all games in provider)
     */
    public function bulkProviderToggle(Request $request)
    {
        $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'action' => 'required|in:enable,disable',
        ]);

        $catId = (int) $request->input('category_id');
        $action = $request->input('action');
        $newView = ($action === 'enable') ? 1 : 0;

        $category = DB::table('categories')->where('id', $catId)->first();
        $targetIds = DB::table('game_categories')->where('category_id', $catId)->pluck('game_id')->toArray();

        if (empty($targetIds)) {
            $msg = "No games linked to provider '{$category->title}'.";
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg]);
            }
            return redirect()->back()->withErrors($msg);
        }

        $affected = DB::table('games')
            ->where(function ($q) use ($targetIds) {
                $q->whereIn('games.id', $targetIds)
                    ->orWhere(function ($sub) use ($targetIds) {
                        $sub->where('games.original_id', '>', 0)
                            ->whereIn('games.original_id', $targetIds);
                    });
            })
            ->update(['view' => $newView]);

        $statusWord = ($newView === 1) ? 'ENABLED' : 'DISABLED';
        $msg = "Provider '{$category->title}': {$statusWord} all {$affected} games successfully.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $affected,
                'category_id' => $catId,
                'action' => $action,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Bulk Action on checked games (Enable / Disable)
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'game_ids' => 'required|array',
            'game_ids.*' => 'integer',
            'action' => 'required|in:enable,disable',
        ]);

        $gameIds = $request->input('game_ids', []);
        $action = $request->input('action');
        $newView = ($action === 'enable') ? 1 : 0;

        $affected = DB::table('games')->whereIn('id', $gameIds)->update(['view' => $newView]);

        $statusWord = ($newView === 1) ? 'enabled' : 'disabled';
        $msg = "Successfully {$statusWord} {$affected} selected games.";

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'count' => $affected,
                'message' => $msg,
            ]);
        }

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Update Bet Limits & Denomination Parameters
     */
    public function updateParams(Request $request, $gameId)
    {
        $request->validate([
            'bet' => 'required|string|max:255',
            'denomination' => 'nullable|numeric|min:0.01',
        ]);

        $game = DB::table('games')->where('id', $gameId)->first();
        if (!$game) {
            return redirect()->back()->withErrors('Game not found.');
        }

        $update = [
            'bet' => trim($request->input('bet')),
        ];

        if ($request->filled('denomination')) {
            $update['denomination'] = (float) $request->input('denomination');
        }

        DB::table('games')->where('id', $gameId)->update($update);

        return redirect()->back()->with('success', "Limits for game '{$game->title}' updated successfully.");
    }

    /**
     * Update Game Source & Host Location (Default / Custom Folder / External URL)
     */
    public function updateSource(Request $request, $gameId)
    {
        $request->validate([
            'source_type' => 'required|in:default,custom_folder,external_url',
            'custom_path' => 'nullable|string|max:500',
        ]);

        $game = DB::table('games')->where('id', $gameId)->first();
        if (!$game) {
            return redirect()->back()->withErrors('Game not found.');
        }

        DB::table('games')->where('id', $gameId)->update([
            'source_type' => $request->input('source_type', 'default'),
            'custom_path' => trim((string) $request->input('custom_path', '')) ?: null,
        ]);

        return redirect()->back()->with('success', "Source configuration for '{$game->title}' updated successfully.");
    }

    /**
     * Register a New Custom / Manual Game
     */
    public function storeManualGame(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'name' => 'required|string|max:100|alpha_dash|unique:games,name',
            'category_id' => 'required|integer|exists:categories,id',
            'source_type' => 'required|in:default,custom_folder,external_url',
            'custom_path' => 'nullable|string|max:500',
            'bet' => 'nullable|string|max:100',
            'denomination' => 'nullable|numeric|min:0.01',
        ]);

        $shopId = 1;
        $gameId = DB::table('games')->insertGetId([
            'name' => trim($request->input('name')),
            'title' => trim($request->input('title')),
            'shop_id' => $shopId,
            'source_type' => $request->input('source_type', 'default'),
            'custom_path' => trim((string) $request->input('custom_path', '')) ?: null,
            'bet' => trim($request->input('bet', '0.01-1.00')),
            'denomination' => (float) $request->input('denomination', 1.00),
            'view' => 1,
            'bids' => 0,
            'stat_in' => 0,
            'stat_out' => 0,
            'device' => 2,
            'category_temp' => 0,
            'original_id' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Link category
        DB::table('game_categories')->insert([
            'game_id' => $gameId,
            'category_id' => (int) $request->input('category_id'),
        ]);

        return redirect()->back()->with('success', "New game '{$request->input('title')}' created and activated successfully!");
    }

    public function destroy($gameId)
    {
        $game = DB::table('games')->select('id', 'name', 'title')->where('id', $gameId)->first();
        if (!$game) {
            return redirect()->back()->withErrors('Game not found.');
        }

        try {
            DB::transaction(function () use ($gameId, $game) {
                if (Schema::hasTable('game_categories')) {
                    DB::table('game_categories')->where('game_id', $gameId)->delete();
                }
                if (Schema::hasTable('stat_game')) {
                    DB::table('stat_game')->where('game', $game->name)->delete();
                }
                DB::table('games')->where('id', $gameId)->delete();
            });

            $this->deleteGameImages($game->name);
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors('Delete failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Game deleted.');
    }

    public function deactivate($gameId)
    {
        return $this->toggleView(request(), $gameId);
    }

    public function activate($gameId)
    {
        return $this->toggleView(request(), $gameId);
    }

    private function deleteGameImages(string $name): void
    {
        $folder = public_path('frontend/Default/ico');
        $candidates = [
            $folder . DIRECTORY_SEPARATOR . $name . '.jpg',
            $folder . DIRECTORY_SEPARATOR . $name . '.jpeg',
            $folder . DIRECTORY_SEPARATOR . $name . '.png',
            $folder . DIRECTORY_SEPARATOR . $name . '.webp',
            $folder . DIRECTORY_SEPARATOR . $name . '.gif',
        ];

        foreach ($candidates as $path) {
            if (File::exists($path)) {
                File::delete($path);
            }
        }
    }
}
