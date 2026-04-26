<?php

namespace App\Services;

use App\Enums\LoanItemCondition;
use App\Models\Book;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use Carbon\CarbonImmutable;

class StatisticsService
{
    /**
     * Tổng hợp số liệu dashboard: tổng quan + chuỗi mượn/trả theo ngày/tháng/năm.
     *
     * @return array{
     *   granularity:string,
     *   summary:array{
     *     total_books:int,
     *     total_registered_cards:int,
     *     active_borrowers:int,
     *     books_on_loan:int,
     *     lost_books:int,
     *     overdue_loans:int,
     *     today_borrowed:int
     *   },
     *   series:list<array{key:string,label:string,borrowed:int,returned:int}>,
     *   forecast:array{next_label:string,expected_borrowed:int}
     * }
     */
    public function dashboardStatistics(string $granularity = 'month'): array
    {
        $granularity = in_array($granularity, ['day', 'month', 'year'], true) ? $granularity : 'month';
        [$periods, $startAt, $endAtExclusive] = $this->buildChartPeriods($granularity);

        $borrowBuckets = [];
        $returnBuckets = [];
        foreach ($periods as $period) {
            $borrowBuckets[$period['key']] = 0;
            $returnBuckets[$period['key']] = 0;
        }

        $borrowRows = Loan::query()
            ->whereDate('loan_date', '>=', $startAt->toDateString())
            ->whereDate('loan_date', '<', $endAtExclusive->toDateString())
            ->get(['loan_date']);

        foreach ($borrowRows as $loan) {
            if ($loan->loan_date === null) {
                continue;
            }
            $key = $this->formatPeriodKey(CarbonImmutable::parse($loan->loan_date), $granularity);
            if (array_key_exists($key, $borrowBuckets)) {
                $borrowBuckets[$key]++;
            }
        }

        $returnRows = Loan::query()
            ->whereNotNull('return_date')
            ->whereDate('return_date', '>=', $startAt->toDateString())
            ->whereDate('return_date', '<', $endAtExclusive->toDateString())
            ->get(['return_date']);

        foreach ($returnRows as $loan) {
            if ($loan->return_date === null) {
                continue;
            }
            $key = $this->formatPeriodKey(CarbonImmutable::parse($loan->return_date), $granularity);
            if (array_key_exists($key, $returnBuckets)) {
                $returnBuckets[$key]++;
            }
        }

        $series = [];
        foreach ($periods as $period) {
            $series[] = [
                'key' => $period['key'],
                'label' => $period['label'],
                'borrowed' => $borrowBuckets[$period['key']] ?? 0,
                'returned' => $returnBuckets[$period['key']] ?? 0,
            ];
        }

        $openStatuses = [Loan::STATUS_BORROWED, Loan::STATUS_OVERDUE];
        $summary = [
            'total_books' => (int) Book::query()->sum('quantity'),
            'total_registered_cards' => (int) LibraryCard::query()->count(),
            'active_borrowers' => (int) Loan::query()
                ->whereIn('status', $openStatuses)
                ->distinct()
                ->count('library_card_id'),
            'books_on_loan' => (int) LoanItem::query()
                ->join('loans', 'loan_items.loan_id', '=', 'loans.id')
                ->whereIn('loans.status', $openStatuses)
                ->sum('loan_items.quantity'),
            'lost_books' => (int) LoanItem::query()
                ->join('loans', 'loan_items.loan_id', '=', 'loans.id')
                ->where('loans.status', Loan::STATUS_RETURNED)
                ->where('loan_items.condition_on_return', LoanItemCondition::LOST->value)
                ->sum('loan_items.quantity'),
            'overdue_loans' => (int) Loan::query()
                ->where('status', Loan::STATUS_OVERDUE)
                ->count(),
            'today_borrowed' => (int) Loan::query()
                ->whereDate('loan_date', now()->toDateString())
                ->count(),
        ];

        return [
            'granularity' => $granularity,
            'summary' => $summary,
            'series' => $series,
            'forecast' => $this->forecastNextBorrow($series, $granularity),
        ];
    }

    /**
     * @return array{0:list<array{key:string,label:string}>,1:CarbonImmutable,2:CarbonImmutable}
     */
    private function buildChartPeriods(string $granularity): array
    {
        $now = CarbonImmutable::now();
        $periods = [];

        if ($granularity === 'day') {
            $count = 30;
            $start = $now->startOfDay()->subDays($count - 1);
            for ($i = 0; $i < $count; $i++) {
                $d = $start->addDays($i);
                $periods[] = ['key' => $d->format('Y-m-d'), 'label' => $d->format('d/m')];
            }

            return [$periods, $start, $now->startOfDay()->addDay()];
        }

        if ($granularity === 'year') {
            $count = 5;
            $start = $now->startOfYear()->subYears($count - 1);
            for ($i = 0; $i < $count; $i++) {
                $d = $start->addYears($i);
                $periods[] = ['key' => $d->format('Y'), 'label' => $d->format('Y')];
            }

            return [$periods, $start, $now->startOfYear()->addYear()];
        }

        $count = 12;
        $start = $now->startOfMonth()->subMonths($count - 1);
        for ($i = 0; $i < $count; $i++) {
            $d = $start->addMonths($i);
            $periods[] = ['key' => $d->format('Y-m'), 'label' => $d->format('m/Y')];
        }

        return [$periods, $start, $now->startOfMonth()->addMonth()];
    }

    private function formatPeriodKey(CarbonImmutable $date, string $granularity): string
    {
        return match ($granularity) {
            'day' => $date->format('Y-m-d'),
            'year' => $date->format('Y'),
            default => $date->format('Y-m'),
        };
    }

    /**
     * @param  list<array{borrowed:int}>  $series
     * @return array{next_label:string,expected_borrowed:int}
     */
    private function forecastNextBorrow(array $series, string $granularity): array
    {
        $values = array_map(static fn (array $item): int => (int) ($item['borrowed'] ?? 0), $series);
        $n = count($values);
        $nextLabel = match ($granularity) {
            'day' => CarbonImmutable::now()->addDay()->format('d/m'),
            'year' => CarbonImmutable::now()->addYear()->format('Y'),
            default => CarbonImmutable::now()->addMonth()->format('m/Y'),
        };

        if ($n === 0) {
            return ['next_label' => $nextLabel, 'expected_borrowed' => 0];
        }

        if ($n === 1) {
            return ['next_label' => $nextLabel, 'expected_borrowed' => max(0, $values[0])];
        }

        $sumX = 0.0;
        $sumY = 0.0;
        $sumXY = 0.0;
        $sumX2 = 0.0;

        foreach ($values as $index => $value) {
            $x = (float) ($index + 1);
            $y = (float) $value;
            $sumX += $x;
            $sumY += $y;
            $sumXY += ($x * $y);
            $sumX2 += ($x * $x);
        }

        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        $slope = $denominator === 0.0 ? 0.0 : (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        $predicted = (int) round(max(0.0, $intercept + ($slope * ($n + 1))));

        return [
            'next_label' => $nextLabel,
            'expected_borrowed' => $predicted,
        ];
    }
}
