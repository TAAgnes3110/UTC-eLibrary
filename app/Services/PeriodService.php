<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Period;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Niên khóa (bảng periods): K = năm vào − 1959; khóa mới bổ sung kể từ 1/8 năm tuyển sinh.
 */
final class PeriodService
{
    /** Quy ước số khóa K (vd. K63 ↔ 2022). */
    public const K_BASE_YEAR = 1959;

    /** Độ dài chương trình (năm tốt nghiệp = năm vào + SPAN). */
    public const PROGRAM_SPAN_YEARS = 4;

    /** Tháng bắt đầu coi là đã vào đợt tuyển sinh mới (tháng 8). */
    public const ADMISSION_MONTH = 8;

    /**
     * @return array{code: string, name: string, start_year: int, end_year: int, is_active: true, sort_order: int}
     */
    public static function buildAttributesForStartYear(int $startYear): array
    {
        $endYear = $startYear + self::PROGRAM_SPAN_YEARS;
        $k = $startYear - self::K_BASE_YEAR;

        return [
            'code' => 'NK'.$startYear,
            'name' => "K{$k} ({$startYear} - {$endYear})",
            'start_year' => $startYear,
            'end_year' => $endYear,
            'is_active' => true,
            'sort_order' => $startYear * 10,
        ];
    }

    /**
     * Tạo các bản ghi niên khóa còn thiếu khi đã qua 1/8 của năm tuyển sinh tương ứng.
     *
     * @return int số bản ghi mới tạo
     */
    public function syncDueCohorts(?CarbonInterface $now = null): int
    {
        $now = $now ? Carbon::parse($now) : Carbon::now();
        $timezone = config('app.timezone') ?: 'UTC';

        $maxStart = Period::query()->max('start_year');
        if ($maxStart === null) {
            return 0;
        }

        $year = (int) $maxStart + 1;
        $created = 0;
        $upperBound = (int) $maxStart + 30;

        while ($year <= $upperBound) {
            $gate = Carbon::create($year, self::ADMISSION_MONTH, 1, 0, 0, 0, $timezone);
            if ($now->lt($gate)) {
                break;
            }

            $code = 'NK'.$year;
            if (! Period::query()->where('code', $code)->exists()) {
                Period::query()->create(self::buildAttributesForStartYear($year));
                $created++;
            }
            $year++;
        }

        if ($created > 0) {
            MasterDataService::clearCache();
        }

        return $created;
    }
}
