<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Support\Facades\Cache;

class TaxonomyCacheService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const KEY_CATEGORIES = 'taxonomy:categories';
    private const KEY_PUBLISHERS = 'taxonomy:publishers';

    public function getCategories(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(self::KEY_CATEGORIES, self::CACHE_TTL_SECONDS, function () {
            return Category::where('is_active', true)
                ->orderBy('order')
                ->orderBy('name')
                ->get(['id', 'code', 'name']);
        });
    }

    public function getPublishers(): \Illuminate\Database\Eloquent\Collection
    {
        return Cache::remember(self::KEY_PUBLISHERS, self::CACHE_TTL_SECONDS, function () {
            return Publisher::where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']);
        });
    }

    /**
     * Clear taxonomy cache (call after categories/publishers are updated).
     */
    public function clear(): void
    {
        Cache::forget(self::KEY_CATEGORIES);
        Cache::forget(self::KEY_PUBLISHERS);
    }
}
