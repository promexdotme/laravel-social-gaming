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
        $snippet = <<<HTML
<style>
#game-home-btn{position:fixed;top:12px;right:12px;z-index:9999;background:#ec1380;color:#fff;border:none;border-radius:999px;padding:10px 16px;font-weight:700;cursor:pointer;box-shadow:0 6px 18px rgba(0,0,0,0.35);}
</style>
<button id="game-home-btn" onclick="window.location.href='/'">Home</button>
HTML;

        if (strpos($content, 'game-home-btn') === false) {
            $content = str_ireplace('</body>', $snippet . '</body>', $content);
            $response->setContent($content);
        }

        return $response;
    }
}
