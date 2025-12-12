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
        return $this->listGames($request, false);
    }

    public function inactive(Request $request)
    {
        return $this->listGames($request, true);
    }

    private function listGames(Request $request, bool $inactive)
    {
        $perPage = 20;
        $term = trim((string) $request->input('q', ''));

        $table = $inactive ? 'games_inactive' : 'games';
        $query = DB::table($table)->select('id', 'name', 'title', 'shop_id')->orderByDesc('id');

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', '%' . $term . '%')
                    ->orWhere('name', 'like', '%' . $term . '%');
            });
        }

        $games = $query->paginate($perPage)->appends($request->only('q'));

        return view('liteback.games.index', [
            'games' => $games,
            'term' => $term,
            'inactive' => $inactive,
        ]);
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
                    // stat_game uses game string column, not game_id.
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
        if (!Schema::hasTable('games_inactive')) {
            return redirect()->back()->withErrors('Inactive table missing. Run migrations.');
        }

        try {
            DB::transaction(function () use ($gameId) {
                $game = DB::table('games')->where('id', $gameId)->lockForUpdate()->first();
                if (!$game) {
                    throw new \RuntimeException('Game not found.');
                }

                $this->moveRow('games', 'games_inactive', $game);
                DB::table('games')->where('id', $gameId)->delete();
            });
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors('In-activate failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Game moved to inactive.');
    }

    public function activate($gameId)
    {
        if (!Schema::hasTable('games_inactive')) {
            return redirect()->back()->withErrors('Inactive table missing. Run migrations.');
        }

        try {
            DB::transaction(function () use ($gameId) {
                $game = DB::table('games_inactive')->where('id', $gameId)->lockForUpdate()->first();
                if (!$game) {
                    throw new \RuntimeException('Inactive game not found.');
                }

                DB::table('games')->where('id', $gameId)->delete();
                $this->moveRow('games_inactive', 'games', $game);
                DB::table('games_inactive')->where('id', $gameId)->delete();
            });
        } catch (\Throwable $e) {
            return redirect()->back()->withErrors('Activate failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Game reactivated.');
    }

    private function moveRow(string $from, string $to, object $row): void
    {
        $sourceCols = Schema::getColumnListing($from);
        $destCols = Schema::getColumnListing($to);

        $data = (array) $row;
        $payload = [];
        foreach ($destCols as $col) {
            if (array_key_exists($col, $data)) {
                $payload[$col] = $data[$col];
            }
        }

        DB::table($to)->insert($payload);
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
