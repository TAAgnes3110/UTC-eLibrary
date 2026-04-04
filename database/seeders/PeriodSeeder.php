<?php

namespace Database\Seeders;

use App\Models\Period;
use App\Services\PeriodService;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    /**
     * Niên khóa: nhãn K theo PeriodService (K = năm vào − 1959).
     */
    public function run(): void
    {
        $startYears = [2022, 2023, 2024, 2025];

        foreach ($startYears as $startYear) {
            $attrs = PeriodService::buildAttributesForStartYear($startYear);
            Period::query()->updateOrCreate(
                ['code' => $attrs['code']],
                $attrs
            );
        }
    }
}
