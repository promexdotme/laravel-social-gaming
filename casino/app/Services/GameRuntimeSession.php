<?php

namespace VanguardLTE\Services;

use Illuminate\Http\Request;

/** Short-lived material shared by the local PHP endpoint and compiled browser runtime. */
final class GameRuntimeSession
{
    public const VERSION = 2;
    public const LIFETIME = 1800;
    public const MAX_CLOCK_SKEW = 60;

    public static function issue(Request $request, string $game): array
    {
        if (!$request->user() || !LicenseService::canPlayGame($game)) {
            throw new \RuntimeException('An active game entitlement is required.');
        }
        $expires = time() + self::LIFETIME;
        return [
            'version' => self::VERSION,
            'expires' => $expires,
            'key' => base64_encode(self::key($request, $game, $expires)),
        ];
    }

    public static function verify(Request $request, string $game): bool
    {
        $expires = $request->header('X-Promex-Expires');
        $sequence = $request->header('X-Promex-Sequence');
        $proof = $request->header('X-Promex-Proof');
        if ($request->header('X-Promex-Protocol') !== (string)self::VERSION
            || !is_string($expires) || !preg_match('/^[0-9]{10}$/D', $expires)
            || !is_string($sequence) || !preg_match('/^[1-9][0-9]{0,9}$/D', $sequence)
            || !is_string($proof) || !preg_match('/^[a-f0-9]{64}$/D', $proof)) {
            return false;
        }
        $expiresAt = (int)$expires;
        $now = time();
        if ($expiresAt <= $now || $expiresAt > $now + self::LIFETIME + self::MAX_CLOCK_SKEW) {
            return false;
        }
        $requestId = (string)$request->header('X-Promex-Request', '');
        $sentAt = (string)$request->header('X-Promex-Time', '');
        $canonical = strtoupper($request->method()) . "\n"
            . '/' . ltrim($request->path(), '/') . "\n"
            . $requestId . "\n" . $sentAt . "\n" . $sequence . "\n"
            . $request->getContent();
        try {
            $expected = hash_hmac('sha256', $canonical, self::key($request, $game, $expiresAt));
            return hash_equals($expected, $proof);
        } catch (\RuntimeException $e) {
            return false;
        }
    }

    private static function key(Request $request, string $game, int $expires): string
    {
        $applicationKey = (string)config('app.key', '');
        if ($applicationKey === '') {
            throw new \RuntimeException('Application key is unavailable.');
        }
        $status = LicenseService::getStatus();
        $authoritySeed = is_string($status['runtime_seed'] ?? null)
            ? base64_decode($status['runtime_seed'], true) : false;
        if ($authoritySeed === false || strlen($authoritySeed) !== 32 || ($status['status'] ?? '') !== 'active') {
            throw new \RuntimeException('Signed runtime authority is unavailable.');
        }
        $userId = (string)$request->user()->getAuthIdentifier();
        $scope = implode('|', [
            'promex-runtime-v2', $userId, $request->session()->getId(),
            strtolower(rtrim($request->getHost(), '.')), $game, (string)$expires,
        ]);
        $installationKey = hash_hmac('sha256', $applicationKey, $authoritySeed, true);
        return hash_hmac('sha256', $scope, $installationKey, true);
    }
}
