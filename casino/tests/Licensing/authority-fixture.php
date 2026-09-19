<?php
// Test-only authority using a key generated in memory. No private hub source/config required.
function issueTestCertificate(array $license, string $domain, string $privatePem, int $now): array
{
    $expiry = strtotime($license['valid_until']);
    $claims = [
        'version' => 1, 'product' => 'promex-gaming-suite', 'status' => 'active',
        'license_key_hash' => hash('sha256', $license['license_key']), 'domain' => $domain,
        'plan' => $license['plan'], 'features' => json_decode($license['features'], true),
        'runtime_seed' => base64_encode(random_bytes(32)),
        'issued_at' => $now, 'refresh_after' => min($now + 3600, $expiry),
        'expires_at' => $expiry, 'grace_deadline' => min($now + 259200, $expiry),
    ];
    $payload = json_encode($claims, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (!openssl_sign($payload, $signature, $privatePem, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Unable to sign test fixture');
    }
    return ['signed_payload' => $payload, 'signature' => base64_encode($signature)];
}
