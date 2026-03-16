<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class TaxonomyCacheService
{
    private const COHORTS_CACHE_KEY = 'taxonomy:cohorts';
    private const COHORTS_CACHE_TTL = 3600;

    public function getCohorts(): array
    {
        return Cache::remember(self::COHORTS_CACHE_KEY, self::COHORTS_CACHE_TTL, function () {
            return User::query()
                ->whereNotNull('cohort')
                ->where('cohort', '!=', '')
                ->distinct()
                ->orderBy('cohort')
                ->pluck('cohort')
                ->values()
                ->all();
        });
    }

    public function clear(): void
    {
        Cache::forget(self::COHORTS_CACHE_KEY);
    }
}

