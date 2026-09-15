<?php

namespace VanguardLTE\Games\JuicyFruits;

use VanguardLTE\Games\CustomSlotsBaseServer;

#[\AllowDynamicProperties]
class Server extends CustomSlotsBaseServer
{
    public function get($request, $game)
    {
        $symbols = [
            ['id' => 0, 'name' => 'apple', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 1, 'name' => 'apricot', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,2,5,10]],
            ['id' => 2, 'name' => 'banana', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 3, 'name' => 'gooseberry', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,3,8,12]],
            ['id' => 4, 'name' => 'grapefruit', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 5, 'name' => 'grapes', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 6, 'name' => 'orange', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,5,9,14]],
            ['id' => 7, 'name' => 'peach', 'wild' => false, 'scatter' => false, 'payouts' => [0,0,2,5,10]],
            ['id' => 8, 'name' => 'plum', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 9, 'name' => 'raspberry', 'wild' => false, 'scatter' => false, 'payouts' => [0,1,2,5,10]],
            ['id' => 10, 'name' => 'strawberry', 'wild' => true, 'scatter' => false, 'payouts' => [0,1,10,25,100]],
            ['id' => 11, 'name' => 'watermelon', 'wild' => false, 'scatter' => true, 'payouts' => [0,1,5,15,30]],
        ];

        $reels = [
            [0,1,2,3,4,5,6,7,8,9,10,11],
            [1,2,3,4,5,6,7,8,9,10,11,0],
            [2,3,4,5,6,7,8,9,10,11,0,1],
            [3,4,5,6,7,8,9,10,11,0,1,2],
            [4,5,6,7,8,9,10,11,0,1,2,3],
        ];

        return $this->handleRequest($request, $game, 'juicy-fruits', 'Juicy Fruits', $symbols, $reels);
    }
}
