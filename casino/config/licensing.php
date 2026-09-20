<?php

return [
    // Deployment trust anchor. Never load this value from a request, database setting, or the hub response.
    'public_key' => \VanguardLTE\Services\LicenseService::PROMEX_PUBLIC_KEY,
    'official_url' => 'https://promex.me/platforms/promex-gaming-suite/',
];
