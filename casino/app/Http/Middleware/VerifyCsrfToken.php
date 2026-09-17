<?php 
namespace VanguardLTE\Http\Middleware
{
    class VerifyCsrfToken extends \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken
    {
        protected function inExceptArray($request)
        {
            // Store/license mutations must not inherit the legacy blanket Liteback exemption.
            if ($request->is('liteback/store/*')) {
                return false;
            }
            return parent::inExceptArray($request);
        }

        protected $except = [
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
