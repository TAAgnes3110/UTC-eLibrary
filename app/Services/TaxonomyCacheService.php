<?php

namespace App\Services;

use App\Models\Category;
use App\Models\LibrarySetting;
use App\Models\Publisher;
use Illuminate\Support\Facades\Cache;

class TaxonomyCacheService
{
    private const CACHE_TTL_SECONDS = 3600;
    private const KEY_CATEGORIES = 'taxonomy:categories';
    private const KEY_PUBLISHERS = 'taxonomy:publishers';
    private const KEY_COHORTS = 'taxonomy:cohorts';

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
     * Danh sách mã khóa (K61..K67). Dùng cho dropdown đăng ký, thẻ, báo cáo.
     */
    public function getCohorts(): array
    {
        return Cache::remember(self::KEY_COHORTS, self::CACHE_TTL_SECONDS, function () {
            $list = LibrarySetting::get('cohorts_list', []);
            return is_array($list) ? array_values($list) : [];
        });
    }

    /**
     * Mỗi năm chạy 1 lần: thêm 1 khóa mới, xóa khóa cũ nhất (giữ tối đa 7 khóa).
     */
    public function rollCohorts(): array
    {
        $list = $this->getCohortsFromStore();
        $maxCount = max(1, (int) LibrarySetting::get('cohorts_max_count', 7));
        $nextCode = $this->nextCohortCode($list);
        if ($nextCode === null) {
            return $list;
        }
        $newList = array_slice($list, 1);
        $newList[] = $nextCode;
        $newList = array_slice($newList, -$maxCount);
        LibrarySetting::set('cohorts_list', $newList, 'json', 'cohorts');
        Cache::forget(self::KEY_COHORTS);
        return $newList;
    }

    private function getCohortsFromStore(): array
    {
        $list = LibrarySetting::get('cohorts_list', []);
        return is_array($list) ? array_values($list) : [];
    }

    private function nextCohortCode(array $list): ?string
    {
        $max = null;
        foreach ($list as $code) {
            if (preg_match('/^K(\d+)$/i', trim((string) $code), $m)) {
                $n = (int) $m[1];
                if ($max === null || $n > $max) {
                    $max = $n;
                }
            }
        }
        return $max === null ? 'K63' : 'K' . ($max + 1);
    }

    /**
     * Clear taxonomy cache (call after categories/publishers/cohorts are updated).
     */
    public function clear(): void
    {
        Cache::forget(self::KEY_CATEGORIES);
        Cache::forget(self::KEY_PUBLISHERS);
        Cache::forget(self::KEY_COHORTS);
    }
}
