<?php

namespace VanguardLTE\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ReferralTracking
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->filled('ref')) {
            $ref = trim($request->get('ref'));
            session(['cedar_ref' => $ref]);
            $response = $next($request);
            return $response->cookie('cedar_ref', $ref, 60 * 24 * 30); // 30 days
        }

        return $next($request);
    }
}
