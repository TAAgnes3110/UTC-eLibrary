<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class MasterLookupCacheService
{
    private const VERSION_KEY = 'api:master-lookup:version';

    public static function remember(string $key, Closure $resolver): mixed
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        $cacheKey = "api:master-lookup:v{$version}:{$key}";

        return Cache::remember($cacheKey, self::ttlSeconds(), $resolver);
    }

    public static function clear(): void
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);
        Cache::forever(self::VERSION_KEY, $version + 1);
    }

    private static function ttlSeconds(): int
    {
        $minutes = (int) env('MASTER_DATA_CACHE_TTL_MINUTES', 10);
        $minutes = max(5, min(30, $minutes));

        return $minutes * 60;
    }
}
