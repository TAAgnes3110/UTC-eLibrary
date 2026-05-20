<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Production: chặn mở /api/* trực tiếp trên trình duyệt (Accept HTML, không có header SPA).
 */
class RestrictApiBrowserAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('security.api.hide_browser_access', false)) {
            return $next($request);
        }

        if ($request->is('api/health', 'api/health/*')) {
            return $next($request);
        }

        if ($this->looksLikeBrowserNavigation($request)) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return $next($request);
    }

    private function looksLikeBrowserNavigation(Request $request): bool
    {
        if (strtoupper($request->method()) !== 'GET') {
            return false;
        }

        if ($request->headers->has('Authorization')) {
            return false;
        }

        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            return false;
        }

        $accept = strtolower((string) $request->header('Accept', ''));

        if ($accept === '' || $accept === '*/*') {
            return false;
        }

        if (str_contains($accept, 'application/json')) {
            return false;
        }

        return str_contains($accept, 'text/html');
    }
}
