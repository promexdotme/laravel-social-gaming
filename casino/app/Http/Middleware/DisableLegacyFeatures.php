<?php

namespace VanguardLTE\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DisableLegacyFeatures
{
    /**
    * Routes to disable in lite mode (matched by route name prefix or exact).
    */
    protected array $blockedPrefixes = [
        'backend.happyhour.',
        'backend.progress.',
        'backend.invite', // if present
        'backend.welcome_bonus.',
        'backend.sms_bonus.',
        'backend.wheelfortune',
        'backend.pincode.',
        'backend.sms_mailing.',
        'backend.info.',
        'backend.article.',
        'backend.rule.',
        'backend.faq.',
        'backend.api.',
        'backend.permission.',
        'backend.role.',
    ];

    protected array $blockedExact = [];

    public function handle(Request $request, Closure $next)
    {
        $name = $request->route() ? $request->route()->getName() : null;
        if ($name) {
            foreach ($this->blockedPrefixes as $prefix) {
                if (Str::startsWith($name, $prefix)) {
                    abort(404);
                }
            }
            if (in_array($name, $this->blockedExact, true)) {
                abort(404);
            }
        }

        return $next($request);
    }
}
