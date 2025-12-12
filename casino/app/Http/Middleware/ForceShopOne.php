<?php

namespace VanguardLTE\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForceShopOne
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();
            if (isset($user->shop_id) && $user->shop_id != 1) {
                $user->shop_id = 1;
                // Persist quietly without touching timestamps.
                $user->timestamps = false;
                $user->save();
            }
        }

        return $next($request);
    }
}
