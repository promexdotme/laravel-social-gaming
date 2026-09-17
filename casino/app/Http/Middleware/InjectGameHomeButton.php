<?php

namespace VanguardLTE\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectGameHomeButton
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var \Symfony\Component\HttpFoundation\Response $response */
        $response = $next($request);

        if (stripos($response->headers->get('Content-Type', ''), 'text/html') === false) {
            return $response;
        }

        $content = $response->getContent();
        // Inject before any game scripts, including direct HTTP clients and legacy XHR engines.
        $sessionScript = '<script src="/js/game-session.js?v=1" data-csrf="'
            . htmlspecialchars($request->session()->token(), ENT_QUOTES, 'UTF-8') . '"></script>';
        if (strpos($content, '/js/game-session.js') === false) {
            $content = preg_replace('/<head\b[^>]*>/i', '$0' . $sessionScript, $content, 1);
        }
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'same-origin');

        $snippet = <<<HTML
<style>
#game-home-btn{position:fixed;top:12px;right:12px;z-index:9999;background:#ec1380;color:#fff;border:none;border-radius:999px;padding:10px 16px;font-weight:700;cursor:pointer;box-shadow:0 6px 18px rgba(0,0,0,0.35);}
</style>
<button id="game-home-btn" onclick="window.location.href='/'">Home</button>
HTML;

        if (strpos($content, 'game-home-btn') === false) {
            $content = str_ireplace('</body>', $snippet . '</body>', $content);
        }

        $response->setContent($content);
        return $response;
    }
}
