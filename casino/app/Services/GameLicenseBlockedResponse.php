<?php

namespace VanguardLTE\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use VanguardLTE\Game;

final class GameLicenseBlockedResponse
{
    public static function make(Request $request, string $game): Response
    {
        $user = $request->user();
        $isAdmin = $user && $user->hasRole('admin');
        $title = Game::where('name', $game)->value('title') ?: trim((string)preg_replace('/(?<!^)([A-Z])/', ' $1', $game));

        return response()
            ->view('frontend.license-blocked', [
                'gameTitle' => $title,
                'isAdmin' => (bool)$isAdmin,
                'manageUrl' => $isAdmin ? route('liteback.store.index') : null,
                'officialUrl' => (string)config('licensing.official_url'),
            ], 403)
            ->withHeaders([
                'Cache-Control' => 'private, no-store, max-age=0',
                'Referrer-Policy' => 'same-origin',
                'X-Content-Type-Options' => 'nosniff',
            ]);
    }
}
