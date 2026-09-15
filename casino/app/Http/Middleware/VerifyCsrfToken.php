<?php 
namespace VanguardLTE\Http\Middleware
{
    class VerifyCsrfToken extends \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken
    {
        protected $except = [
            '/game/*/server', 
            'game/*/server',
            '/payment/interkassa/result', 
            '/payment/coinbase/result', 
            '/payment/btcpayserver/result', 
            '/sms/callback', 
            '/profile/contact',
            '/refill-coins',
            'refill-coins',
            '/sports/bet',
            'sports/bet',
            '/lotto/play',
            'lotto/play',
            '/liteback/*',
            'liteback/*',
            'register'
        ];
    }

}
