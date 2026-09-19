<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private array $cards = ['CedarHiLo' => 'Cedar Hi-Lo', 'CedarBlackjack' => 'Cedar Blackjack'];
    private array $folderOriginals = ['CedarLimbo','CedarTower','CedarKeno','CedarCoinFlip','CedarGoal','CedarTreasure'];

    public function up(): void
    {
        $template = DB::table('games')->where('name', 'CedarWheel')->first();
        if (!$template) throw new RuntimeException('CedarWheel is required before adding Cedar Cards.');
        $cardsCategory = DB::table('categories')->where('href', 'cedar_cards')->value('id');
        if (!$cardsCategory) $cardsCategory = DB::table('categories')->insertGetId([
            'title' => 'CEDAR Cards', 'parent' => 0, 'position' => 2, 'href' => 'cedar_cards', 'original_id' => 0, 'shop_id' => 1,
        ]);
        foreach ($this->cards as $name => $title) {
            $id = DB::table('games')->where('name', $name)->value('id');
            if (!$id) {
                $row = (array) $template; unset($row['id']);
                $row['name'] = $name; $row['title'] = $title; $row['label'] = 'CARDS';
                $row['source_type'] = 'custom_folder'; $row['custom_path'] = "/games/$name/index.html";
                $row['original_id'] = 0; $row['created_at'] = now(); $row['updated_at'] = now();
                $id = DB::table('games')->insertGetId($row);
            }
            DB::table('games')->where('id', $id)->update(['original_id' => $id, 'source_type' => 'custom_folder', 'custom_path' => "/games/$name/index.html"]);
            DB::table('game_categories')->updateOrInsert(['game_id' => $id, 'category_id' => $cardsCategory], []);
        }
        foreach ($this->folderOriginals as $name) {
            $id = DB::table('games')->where('name', $name)->value('id');
            if ($id) DB::table('games')->where('id', $id)->update(['original_id' => $id]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('games')->whereIn('name', array_keys($this->cards))->pluck('id');
        DB::table('game_categories')->whereIn('game_id', $ids)->delete();
        DB::table('games')->whereIn('id', $ids)->delete();
        $category = DB::table('categories')->where('href', 'cedar_cards')->value('id');
        if ($category && !DB::table('game_categories')->where('category_id', $category)->exists()) DB::table('categories')->where('id', $category)->delete();
    }
};
