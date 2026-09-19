<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private array $games = [
        'CedarLimbo' => 'Cedar Limbo', 'CedarTower' => 'Cedar Tower', 'CedarKeno' => 'Cedar Keno',
        'CedarCoinFlip' => 'Cedar Coin Flip', 'CedarGoal' => 'Cedar Goal', 'CedarTreasure' => 'Cedar Treasure',
    ];

    public function up(): void
    {
        $template = DB::table('games')->where('name', 'CedarWheel')->first();
        if (!$template) throw new RuntimeException('CedarWheel is required before adding Cedar Originals.');
        $category = DB::table('categories')->where('href', 'cedar_games')->value('id');
        foreach ($this->games as $name => $title) {
            $id = DB::table('games')->where('name', $name)->value('id');
            if (!$id) {
                $row = (array) $template; unset($row['id']);
                $row['name'] = $name; $row['title'] = $title; $row['label'] = 'NEW';
                $row['source_type'] = 'custom_folder'; $row['custom_path'] = "/games/$name/index.html";
                $row['original_id'] = 0; $row['created_at'] = now(); $row['updated_at'] = now();
                $id = DB::table('games')->insertGetId($row);
            }
            if ($category) DB::table('game_categories')->updateOrInsert(['game_id' => $id, 'category_id' => $category], []);
        }
    }

    public function down(): void
    {
        $ids = DB::table('games')->whereIn('name', array_keys($this->games))->pluck('id');
        DB::table('game_categories')->whereIn('game_id', $ids)->delete();
        DB::table('games')->whereIn('id', $ids)->delete();
    }
};
