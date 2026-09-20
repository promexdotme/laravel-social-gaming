<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Game unavailable</title>
</head>
<body>
    <main id="game-license-blocked"
          data-game="{{ $gameTitle }}"
          data-admin="{{ $isAdmin ? '1' : '0' }}"
          data-lobby-url="{{ url('/') }}"
          data-manage-url="{{ $manageUrl ?? '' }}"
          data-official-url="{{ $officialUrl }}"></main>
    <noscript>
        <p>This game is temporarily unavailable. <a href="{{ url('/') }}">Return to the lobby</a>.</p>
    </noscript>
    <script src="/js/game-license-blocked.js?v=1"></script>
</body>
</html>
