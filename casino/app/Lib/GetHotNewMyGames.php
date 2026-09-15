<?php

namespace VanguardLTE\Lib;

use Detection\MobileDetect;
use Illuminate\Support\Facades\Cache;
use VanguardLTE\Game;
use VanguardLTE\StatGame;

class GetHotNewMyGames
{
    public static function get_new_games($finded = false){
        $is_mobile = 0;
        $shop_id = (isset(auth()->user()->shop_id) ? auth()->user()->shop_id : 1);

        $detect = new MobileDetect;
        if( $detect->isMobile() || $detect->isTablet() ){
            $is_mobile = 1;
        }

        if (Cache::has('new_games:'. $shop_id .':'.$is_mobile)) {
            $data = Cache::get('new_games:'. $shop_id .':'.$is_mobile);
        } else {
            $random_20_games = [];
            $last_30_games = Game::where(['view' => 1, 'shop_id' => $shop_id]);
            
            if( $is_mobile ){
                $last_30_games = $last_30_games->whereIn('device', [0,2]);
            }else{
                $last_30_games = $last_30_games->whereIn('device', [1,2]);
            }
            $last_30_games = $last_30_games->orderBy('id', 'DESC')
                ->take(30)
                ->get();
            if( $last_30_games && count($last_30_games) ){
                $takeCount = min(20, count($last_30_games));
                $random_20_games = $last_30_games->random($takeCount)->pluck('id');
                if($random_20_games && count($random_20_games)){
                    $random_20_games = $random_20_games->toArray();
                }
            }
            Cache::put('new_games:'.$shop_id.':'.$is_mobile, $random_20_games, 60*60*3);
            $data = $random_20_games;
        }

        if($finded){
            return ( $data && count($data) );
        }
        return $data ?: [0];
    }

    public static function get_my_games($finded = false){
        $my_games_stat = StatGame::where('user_id', (isset(auth()->user()->id) ? auth()->user()->id : 0))->groupBy('game')->take(20)->pluck('game');
        if($my_games_stat && count($my_games_stat)){
            $my_games = Game::where(['view' => 1, 'shop_id' => (isset(auth()->user()->shop_id) ? auth()->user()->shop_id : 1)])->whereIn('name', $my_games_stat);
            $detect = new MobileDetect;
            if( $detect->isMobile() || $detect->isTablet() ){
                $my_games = $my_games->whereIn('device', [0,2]);
            }else{
                $my_games = $my_games->whereIn('device', [1,2]);
            }
            $my_games = $my_games->take(20)->pluck('id');
            if($my_games && count($my_games)){
                if($finded){
                    return true;
                }
                return $my_games->toArray();
            }
        }
        return $finded ? false : [0];
    }

    public static function get_hot_games($finded = false){
        $is_mobile = 0;
        $shop_id = (isset(auth()->user()->shop_id) ? auth()->user()->shop_id : 1);

        $detect = new MobileDetect;
        if( $detect->isMobile() || $detect->isTablet() ){
            $is_mobile = 1;
        }

        if (Cache::has('hot_games:'. $shop_id .':'.$is_mobile)) {
            $data = Cache::get('hot_games:'. $shop_id .':'.$is_mobile);
        } else {
            $hot_ids = [];

            // 1. Check for games played in stat history
            $hot_games_stat = StatGame::where('shop_id', $shop_id)->groupBy('game')->take(100)->pluck('game');
            if($hot_games_stat && count($hot_games_stat)){
                $query = Game::where(['view' => 1, 'shop_id' => $shop_id])->whereIn('name', $hot_games_stat);
                if( $is_mobile ){
                    $query = $query->whereIn('device', [0,2]);
                }else{
                    $query = $query->whereIn('device', [1,2]);
                }
                $hot_ids = $query->take(20)->pluck('id')->toArray();
            }

            // 2. If fewer than 20 games found, randomly select from active game catalog
            if (count($hot_ids) < 20) {
                $needed = 20 - count($hot_ids);
                $randomQuery = Game::where(['view' => 1, 'shop_id' => $shop_id]);
                if ($is_mobile) {
                    $randomQuery = $randomQuery->whereIn('device', [0, 2]);
                } else {
                    $randomQuery = $randomQuery->whereIn('device', [1, 2]);
                }

                if (!empty($hot_ids)) {
                    $randomQuery = $randomQuery->whereNotIn('id', $hot_ids);
                }

                $random_ids = $randomQuery->inRandomOrder()->take($needed)->pluck('id')->toArray();
                $hot_ids = array_merge($hot_ids, $random_ids);
            }

            Cache::put('hot_games:'.$shop_id.':'.$is_mobile, $hot_ids, 60*60*3);
            $data = $hot_ids;
        }

        if($finded){
            return ( $data && count($data) );
        }
        return $data ?: [0];
    }
}
