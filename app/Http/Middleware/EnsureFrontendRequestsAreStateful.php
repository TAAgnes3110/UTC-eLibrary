<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful as SanctumEnsureFrontendRequestsAreStateful;

/**
 * Sanctum mặc định chỉ coi request "frontend" khi có Origin/Referer.
 * Truy cập bằng IP (EC2) đôi khi thiếu header đó → API không load session web → 401.
 */
class EnsureFrontendRequestsAreStateful extends SanctumEnsureFrontendRequestsAreStateful
{
    /**
     * @param  Request  $request
     */
    public static function fromFrontend($request): bool
    {
        if (parent::fromFrontend($request)) {
            return true;
        }

        return static::requestHostIsStateful($request);
    }

    protected static function requestHostIsStateful(Request $request): bool
    {
        $host = $request->getHttpHost();
        if ($host === '') {
            return false;
        }

        foreach (array_filter(config('sanctum.stateful', [])) as $domain) {
            $domain = trim((string) $domain);
            if ($domain === '') {
                continue;
            }
            if (strcasecmp($domain, $host) === 0) {
                return true;
            }
            if (! str_contains($domain, ':') && str_starts_with(strtolower($host), strtolower($domain).':')) {
                return true;
            }
        }

        return false;
    }
}
