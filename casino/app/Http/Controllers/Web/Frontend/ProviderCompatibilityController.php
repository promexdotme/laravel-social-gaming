<?php

namespace VanguardLTE\Http\Controllers\Web\Frontend;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VanguardLTE\Http\Controllers\Controller;
use VanguardLTE\Services\LicenseService;

/**
 * Narrow compatibility responses for optional provider requests whose original
 * host is not part of a local game package.
 */
final class ProviderCompatibilityController extends Controller
{
    public function socketConfig(Request $request)
    {
        $this->licensedLocalGame($request);

        $secure = $request->isSecure();
        $port = $request->getPort();
        $host = $request->getHost();

        return response()->json([
            'port' => $port . '/slots',
            'listen_port' => $port,
            'host' => $host,
            'prefix' => $secure ? 'https://' : 'http://',
            'host_ws' => $host,
            'prefix_ws' => $secure ? 'wss://' : 'ws://',
            'ssl' => $secure,
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Vary' => 'Cookie, Referer',
        ]);
    }

    public function operatorLogoInfo(Request $request): Response
    {
        $game = $this->licensedLocalGame($request);
        $providerFile = base_path('../games/' . $game . '/gs2c/common/games-html5/operator_logos/logo_info.js');

        if (is_file($providerFile)) {
            return response()->file($providerFile, $this->privateJavascriptHeaders());
        }

        $fallback = "(function(){'use strict';window.UHTLogotypeInfo=window.UHTLogotypeInfo||{};})();\n";

        return response($fallback, 200, $this->privateJavascriptHeaders());
    }

    public function unreadAnnouncements(Request $request)
    {
        $game = $this->licensedLocalGame($request);
        $this->requireGs2cFamily($game);

        return response()->json([
            'error' => 0,
            'announcements' => [],
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Vary' => 'Cookie, Referer',
        ]);
    }

    public function availableFreeRounds(Request $request)
    {
        $game = $this->licensedLocalGame($request);
        $this->requireGs2cFamily($game);

        return response()->json([
            'freeRounds' => [],
        ])->withHeaders([
            'Cache-Control' => 'private, no-store, max-age=0',
            'Vary' => 'Cookie, Referer',
        ]);
    }

    private function licensedLocalGame(Request $request): string
    {
        if (!$request->user()) {
            abort(401);
        }

        $game = $this->gameFromSameOriginReferer($request);
        if ($game === null || !is_dir(base_path('../games/' . $game))) {
            abort(404);
        }
        $requestDomain = strtolower(rtrim($request->getHost(), '.'));
        if (!hash_equals(LicenseService::licensedDomain(), $requestDomain)) {
            abort(403, 'The game host does not match the licensed domain.');
        }
        if (!LicenseService::canPlayGame($game)) {
            abort(403, 'An active game entitlement is required.');
        }

        return $game;
    }

    private function requireGs2cFamily(string $game): void
    {
        if (!is_dir(base_path('../games/' . $game . '/gs2c'))) {
            abort(404);
        }
    }

    private function gameFromSameOriginReferer(Request $request): ?string
    {
        $referer = (string)$request->headers->get('referer', '');
        $host = parse_url($referer, PHP_URL_HOST);
        $path = parse_url($referer, PHP_URL_PATH);
        if (!is_string($host) || !is_string($path)
            || strcasecmp(rtrim($host, '.'), rtrim($request->getHost(), '.')) !== 0) {
            return null;
        }

        return preg_match('~/(?:game|games)/([A-Za-z0-9_]+)(?:/|$)~D', $path, $matches)
            ? $matches[1]
            : null;
    }

    private function privateJavascriptHeaders(): array
    {
        return [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
            'Vary' => 'Cookie, Referer',
        ];
    }
}
