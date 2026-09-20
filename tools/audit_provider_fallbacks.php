<?php

declare(strict_types=1);

/**
 * Read-only inventory for provider-wide missing request patterns.
 *
 * Usage:
 *   php tools/audit_provider_fallbacks.php
 *   php tools/audit_provider_fallbacks.php --game=BigBassBonanza
 *   php tools/audit_provider_fallbacks.php --json=output/provider-fallbacks.json
 */

$root = dirname(__DIR__);
$gamesRoot = $root . DIRECTORY_SEPARATOR . 'games';
$options = getopt('', ['game::', 'json::']);
$onlyGame = isset($options['game']) && is_string($options['game']) ? $options['game'] : null;
$jsonPath = isset($options['json']) && is_string($options['json']) ? $options['json'] : null;

if (!is_dir($gamesRoot)) {
    fwrite(STDERR, "Games directory not found: {$gamesRoot}\n");
    exit(1);
}
if ($onlyGame !== null && !preg_match('/^[A-Za-z0-9_]+$/D', $onlyGame)) {
    fwrite(STDERR, "Invalid --game value.\n");
    exit(1);
}

/** @return list<string> */
function candidateSourceFiles(string $gamePath): array
{
    $names = [
        $gamePath . '/index.html',
        $gamePath . '/gs2c/html5Game.html',
        $gamePath . '/gs2c/desktop/build.js',
        $gamePath . '/gs2c/mobile/build.js',
    ];

    return array_values(array_filter($names, 'is_file'));
}

/** @param list<string> $files */
function filesContain(array $files, string $needle): bool
{
    foreach ($files as $file) {
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            continue;
        }
        $overlap = '';
        while (!feof($handle)) {
            $chunk = fread($handle, 1024 * 1024);
            if ($chunk === false) {
                break;
            }
            $haystack = $overlap . $chunk;
            if (strpos($haystack, $needle) !== false) {
                fclose($handle);
                return true;
            }
            $overlap = substr($haystack, -max(0, strlen($needle) - 1));
        }
        fclose($handle);
    }

    return false;
}

function structuralFamily(string $gamePath): string
{
    if (is_dir($gamePath . '/gs2c')) {
        return 'gs2c';
    }
    if (is_file($gamePath . '/game/manifest.json')) {
        return 'manifest-game';
    }
    if (is_file($gamePath . '/index.html')) {
        return 'standalone-html5';
    }

    return 'unclassified';
}

$directories = $onlyGame !== null
    ? [$gamesRoot . DIRECTORY_SEPARATOR . $onlyGame]
    : glob($gamesRoot . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
$rows = [];

foreach ($directories as $gamePath) {
    if (!is_dir($gamePath)) {
        continue;
    }
    $game = basename($gamePath);
    $files = candidateSourceFiles($gamePath);
    $logoFile = $gamePath . '/gs2c/common/games-html5/operator_logos/logo_info.js';
    $desktopBootstrap = $gamePath . '/gs2c/desktop/bootstrap.js';
    $logoReference = filesContain($files, '/operator_logos/');
    $announcementReference = filesContain($files, '/gs2c/announcements/unread/');
    $freeRoundReference = filesContain($files, '/gs2c/promo/frb/available/');

    if (!$logoReference && !$announcementReference && structuralFamily($gamePath) !== 'gs2c') {
        continue;
    }

    $rows[] = [
        'game' => $game,
        'family' => structuralFamily($gamePath),
        'root_operator_logo_reference' => $logoReference,
        'packaged_operator_logo_exists' => is_file($logoFile),
        'announcement_poll_reference' => $announcementReference,
        'free_round_poll_reference' => $freeRoundReference,
        'packaged_desktop_bootstrap_exists' => is_file($desktopBootstrap),
        'candidate_sources_scanned' => count($files),
    ];
}

usort($rows, static fn(array $a, array $b): int => [$a['family'], $a['game']] <=> [$b['family'], $b['game']]);
$groups = [];
foreach ($rows as $row) {
    $signature = implode('|', [
        $row['family'],
        $row['root_operator_logo_reference'] ? 'logo-ref' : 'no-logo-ref',
        $row['packaged_operator_logo_exists'] ? 'logo-file' : 'no-logo-file',
        $row['announcement_poll_reference'] ? 'announcements' : 'no-announcements',
        $row['free_round_poll_reference'] ? 'free-rounds' : 'no-free-rounds',
        $row['packaged_desktop_bootstrap_exists'] ? 'bootstrap' : 'no-bootstrap',
    ]);
    if (!isset($groups[$signature])) {
        $groups[$signature] = [
            'family' => $row['family'],
            'root_operator_logo_reference' => $row['root_operator_logo_reference'],
            'packaged_operator_logo_exists' => $row['packaged_operator_logo_exists'],
            'announcement_poll_reference' => $row['announcement_poll_reference'],
            'free_round_poll_reference' => $row['free_round_poll_reference'],
            'packaged_desktop_bootstrap_exists' => $row['packaged_desktop_bootstrap_exists'],
            'games' => [],
        ];
    }
    $groups[$signature]['games'][] = $row['game'];
}

$report = [
    'generated_at' => gmdate('c'),
    'scope' => $onlyGame ?? 'all games',
    'note' => 'Static candidate inventory; confirm actual HTTP status in a signed-in browser before enabling a family.',
    'groups' => array_values($groups),
];

foreach ($report['groups'] as $group) {
    printf(
        "%s | games=%d | logo request=%s | packaged logo=%s | announcements=%s | free rounds=%s | bootstrap=%s\n",
        $group['family'],
        count($group['games']),
        $group['root_operator_logo_reference'] ? 'yes' : 'no',
        $group['packaged_operator_logo_exists'] ? 'yes' : 'no',
        $group['announcement_poll_reference'] ? 'yes' : 'no',
        $group['free_round_poll_reference'] ? 'yes' : 'no',
        $group['packaged_desktop_bootstrap_exists'] ? 'yes' : 'no'
    );
    echo '  ' . implode(', ', $group['games']) . "\n";
}

if ($jsonPath !== null) {
    $resolved = str_starts_with($jsonPath, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $jsonPath)
        ? $jsonPath
        : $root . DIRECTORY_SEPARATOR . $jsonPath;
    $directory = dirname($resolved);
    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        fwrite(STDERR, "Cannot create report directory: {$directory}\n");
        exit(1);
    }
    file_put_contents($resolved, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo "Report written: {$resolved}\n";
}
