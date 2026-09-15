<?php

namespace VanguardLTE\Games\GoldRush;

use VanguardLTE\Games\CustomSlotsBaseServer;

#[\AllowDynamicProperties]
class Server extends CustomSlotsBaseServer
{
    public function get($request, $game)
    {
        $symbols = [
            ['id' => 0, 'name' => 'apple', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 1, 'name' => 'cherry', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,5,10,15]],
            ['id' => 2, 'name' => 'lemon', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 3, 'name' => 'orange', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 4, 'name' => 'grape', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,3,9,12]],
            ['id' => 5, 'name' => 'watermelon', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,2,5,10]],
            ['id' => 6, 'name' => 'bell', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,2,5,10]],
            ['id' => 7, 'name' => 'star', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,2,5,10]],
            ['id' => 8, 'name' => 'bar', 'wild' => true, 'scatter' => false, 'payouts' => [0,0,10,25,100]],
            ['id' => 9, 'name' => 'seven', 'wild' => false, 'scatter' => true, 'payouts' => [0,0,10,20,50]],
        ];

        $reels = [
            [0,1,2,3,4,5,6,7,8,9],
            [1,2,3,4,5,6,7,8,9,0],
            [2,3,4,5,6,7,8,9,0,1],
            [3,4,5,6,7,8,9,0,1,2],
            [4,5,6,7,8,9,0,1,2,3],
        ];

        return $this->handleRequest($request, $game, 'gold-rush', 'Gold Rush', $symbols, $reels);
    }
}
