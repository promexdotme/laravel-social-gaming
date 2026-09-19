<?php

namespace VanguardLTE\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use VanguardLTE\Services\LicenseService;
use VanguardLTE\Services\GameRuntimeSession;

/** Session/CSRF authentication is handled by Laravel's web middleware. */
class ProtectGameRequests
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'authentication_required'], 401);
        }
        if (config('app.env', 'production') === 'production' && !$request->isSecure()) {
            return response()->json(['error' => 'https_required'], 403);
        }
        $game = (string)$request->route('game');
        if (strtolower(rtrim($request->getHost(), '.')) !== LicenseService::licensedDomain()
            || !LicenseService::canPlayGame($game)) {
            return response()->json(['error' => 'license_required'], 403);
        }
        // Reject cross-origin browser requests even if an operator weakens cookie settings.
        $origin = $request->header('Origin');
        if ($origin !== null && $origin !== $request->getSchemeAndHttpHost()) {
            return response()->json(['error' => 'origin_mismatch'], 403);
        }
        $id = $request->header('X-Promex-Request', '');
        $sentAt = $request->header('X-Promex-Time', '');
        if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D', $id)
            || !is_string($sentAt) || !preg_match('/^[0-9]{10}$/D', $sentAt)
            || abs(time() - (int)$sentAt) > 120) {
            return response()->json(['error' => 'invalid_request_identity'], 400);
        }
        if (!GameRuntimeSession::verify($request, $game)) {
            return response()->json(['error' => 'runtime_required'], 403);
        }
        $scope = $request->user()->getAuthIdentifier() . '|' . $request->session()->getId() . '|' . $game . '|' . $id;
        try {
            // add() is atomic in the supported file/Redis stores. Consume before invoking the engine.
            if (!Cache::add('game-request:v1:' . hash('sha256', $scope), true, 300)) {
                return response()->json(['error' => 'duplicate_request'], 409);
            }
        } catch (\Throwable $e) {
            return response()->json(['error' => 'request_store_unavailable'], 503);
        }
        return $next($request);
    }
}
