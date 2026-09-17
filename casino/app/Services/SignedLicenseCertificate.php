<?php

namespace VanguardLTE\Services;

/** Only the authority's private key can create these claims. No local APP_KEY fallback. */
final class SignedLicenseCertificate
{
    public const PRODUCT = 'promex-gaming-suite';
    public const MAX_GRACE = 259200;

    public static function verify(array $envelope, string $publicKey, string $licenseKey, string $domain, int $now, bool $offline = false): ?array
    {
        $payload = $envelope['signed_payload'] ?? null;
        $encodedSignature = $envelope['signature'] ?? null;
        if (!is_string($payload) || strlen($payload) > 16384 || !is_string($encodedSignature)) {
            return null;
        }
        $signature = base64_decode($encodedSignature, true);
        if ($signature === false || openssl_verify($payload, $signature, $publicKey, OPENSSL_ALGO_SHA256) !== 1) {
            return null;
        }
        $c = json_decode($payload, true);
        if (!is_array($c) || ($c['version'] ?? null) !== 1 || ($c['product'] ?? null) !== self::PRODUCT
            || ($c['status'] ?? null) !== 'active' || ($c['domain'] ?? null) !== $domain
            || !is_string($c['license_key_hash'] ?? null)
            || !hash_equals(hash('sha256', $licenseKey), $c['license_key_hash'])) {
            return null;
        }
        foreach (['issued_at', 'refresh_after', 'expires_at', 'grace_deadline'] as $field) {
            if (!is_int($c[$field] ?? null)) {
                return null;
            }
        }
        if ($c['issued_at'] > $now + 60 || $c['issued_at'] <= 0
            || $c['refresh_after'] <= $c['issued_at'] || $c['refresh_after'] > $c['issued_at'] + 3600
            || $c['grace_deadline'] < $c['refresh_after']
            || $c['grace_deadline'] > $c['issued_at'] + self::MAX_GRACE
            || $c['expires_at'] < $c['grace_deadline']
            || $now >= ($offline ? $c['grace_deadline'] : $c['refresh_after'])
            || $now >= $c['expires_at']) {
            return null;
        }
        if (!is_array($c['features'] ?? null) || !array_is_list($c['features'])) {
            return null;
        }
        foreach ($c['features'] as $feature) {
            if (!is_string($feature) || !preg_match('/^[a-z0-9_]+$/D', $feature)) {
                return null;
            }
        }
        if (isset($c['games']) && (!is_array($c['games']) || !array_is_list($c['games']))) {
            return null;
        }
        foreach ($c['games'] ?? [] as $game) {
            if (!is_string($game) || !preg_match('/^[A-Za-z0-9_]+$/D', $game)) {
                return null;
            }
        }
        return $c;
    }
}
