<?php

namespace App\Services;

use App\Enums\LoanItemCondition;
use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class StatisticsService
{
    private const CACHE_TTL_SECONDS = 120;

    /**
     * Tổng hợp số liệu dashboard: tổng quan + chuỗi mượn/trả + mua tài liệu số (granularity độc lập).
     *
     * @return array{
     *   granularity:string,
     *   digital_granularity:string,
     *   summary:array{
     *     total_books:int,
     *     total_registered_cards:int,
     *     active_borrowers:int,
     *     books_on_loan:int,
     *     lost_books:int,
     *     overdue_loans:int,
     *     today_borrowed:int,
     *     digital_books_purchased:int,
     *     digital_revenue_vnd:int
     *   },
     *   series:list<array{key:string,label:string,borrowed:int,returned:int}>,
     *   digital_series:list<array{key:string,label:string,books_sold:int,revenue_vnd:int}>,
     *   forecast:array{next_label:string,expected_borrowed:int}
     * }
     */
    public function dashboardStatistics(string $loanGranularity = 'month', ?string $digitalGranularity = null): array
    {
        return $this->dashboardStatisticsParts(
            $loanGranularity,
            $digitalGranularity,
            ['summary', 'series', 'digital_series', 'forecast'],
        );
    }

    /**
     * @param  list<string>  $parts  summary|series|digital_series|forecast
     * @return array<string, mixed>
     */
    public function dashboardStatisticsParts(
        string $loanGranularity = 'month',
        ?string $digitalGranularity = null,
        array $parts = ['summary', 'series', 'digital_series', 'forecast'],
    ): array {
        $loanGranularity = $this->normalizeGranularity($loanGranularity);
        $digitalGranularity = $this->normalizeGranularity($digitalGranularity ?? $loanGranularity);
        $parts = array_values(array_unique(array_filter(array_map(
            static fn (string $part): string => trim($part),
            $parts,
        ))));

        $payload = [
            'granularity' => $loanGranularity,
            'digital_granularity' => $digitalGranularity,
        ];

        $series = null;
        if (in_array('series', $parts, true) || in_array('forecast', $parts, true)) {
            $series = Cache::remember(
                "dashboard_stats:loan_series:{$loanGranularity}",
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                fn (): array => $this->buildLoanSeries($loanGranularity),
            );
        }

        if (in_array('summary', $parts, true)) {
            $payload['summary'] = Cache::remember(
                'dashboard_stats:summary:v1',
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                fn (): array => $this->buildSummary(),
            );
        }

        if (in_array('series', $parts, true) && $series !== null) {
            $payload['series'] = $series;
        }

        if (in_array('digital_series', $parts, true)) {
            $payload['digital_series'] = Cache::remember(
                "dashboard_stats:digital_series:{$digitalGranularity}",
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                fn (): array => $this->buildDigitalSeries($digitalGranularity),
            );
        }

        if (in_array('forecast', $parts, true) && $series !== null) {
            $payload['forecast'] = $this->forecastNextBorrow($series, $loanGranularity);
        }

        return $payload;
    }

    /**
     * @return list<array{key:string,label:string,borrowed:int,returned:int}>
     */
    private function buildLoanSeries(string $granularity): array
    {
        [$periods, $startAt, $endAtExclusive] = $this->buildChartPeriods($granularity);

        $borrowCounts = $this->aggregateLoanColumnCounts('loan_date', $granularity, $startAt, $endAtExclusive);
        $returnCounts = $this->aggregateLoanColumnCounts('return_date', $granularity, $startAt, $endAtExclusive);

        $series = [];
        foreach ($periods as $period) {
            $series[] = [
                'key' => $period['key'],
                'label' => $period['label'],
                'borrowed' => $borrowCounts[$period['key']] ?? 0,
                'returned' => $returnCounts[$period['key']] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * @return array<string, int>
     */
    private function aggregateLoanColumnCounts(
        string $column,
        string $granularity,
        CarbonImmutable $startAt,
        CarbonImmutable $endAtExclusive,
    ): array {
        $periodExpr = $this->loanPeriodExpression($column, $granularity);

        $query = Loan::query()
            ->whereDate($column, '>=', $startAt->toDateString())
            ->whereDate($column, '<', $endAtExclusive->toDateString());

        if ($column === 'return_date') {
            $query->whereNotNull('return_date');
        }

        return $query
            ->selectRaw("{$periodExpr} as period_key, COUNT(*) as aggregate_count")
            ->groupBy('period_key')
            ->pluck('aggregate_count', 'period_key')
            ->map(static fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return list<array{key:string,label:string,books_sold:int,revenue_vnd:int}>
     */
    private function buildDigitalSeries(string $granularity): array
    {
        [$periods, $startAt, $endAtExclusive] = $this->buildChartPeriods($granularity);

        $revenueCounts = $this->aggregateDigitalOrderRevenue($granularity, $startAt, $endAtExclusive);
        $booksCounts = $this->aggregateDigitalBooksSold($granularity, $startAt, $endAtExclusive);

        $digitalSeries = [];
        foreach ($periods as $period) {
            $digitalSeries[] = [
                'key' => $period['key'],
                'label' => $period['label'],
                'books_sold' => $booksCounts[$period['key']] ?? 0,
                'revenue_vnd' => $revenueCounts[$period['key']] ?? 0,
            ];
        }

        return $digitalSeries;
    }

    /**
     * @return array<string, int>
     */
    private function aggregateDigitalOrderRevenue(
        string $granularity,
        CarbonImmutable $startAt,
        CarbonImmutable $endAtExclusive,
    ): array {
        $periodExpr = $this->orderPaidPeriodExpression('orders', $granularity);
        $paidDateExpr = 'DATE(COALESCE(orders.paid_at, orders.updated_at, orders.created_at))';

        return Order::query()
            ->where('type', Order::TYPE_DIGITAL_PURCHASE)
            ->where('status', Order::STATUS_PAID)
            ->whereRaw("{$paidDateExpr} >= ?", [$startAt->toDateString()])
            ->whereRaw("{$paidDateExpr} < ?", [$endAtExclusive->toDateString()])
            ->selectRaw("{$periodExpr} as period_key, COALESCE(SUM(total_vnd_snapshot), 0) as revenue_vnd")
            ->groupBy('period_key')
            ->pluck('revenue_vnd', 'period_key')
            ->map(static fn ($amount): int => (int) $amount)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    private function aggregateDigitalBooksSold(
        string $granularity,
        CarbonImmutable $startAt,
        CarbonImmutable $endAtExclusive,
    ): array {
        $periodExpr = $this->orderPaidPeriodExpression('orders', $granularity);
        $paidDateExpr = 'DATE(COALESCE(orders.paid_at, orders.updated_at, orders.created_at))';

        return OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.type', Order::TYPE_DIGITAL_PURCHASE)
            ->where('orders.status', Order::STATUS_PAID)
            ->whereRaw("{$paidDateExpr} >= ?", [$startAt->toDateString()])
            ->whereRaw("{$paidDateExpr} < ?", [$endAtExclusive->toDateString()])
            ->selectRaw("{$periodExpr} as period_key, COALESCE(SUM(order_items.quantity), 0) as books_sold")
            ->groupBy('period_key')
            ->pluck('books_sold', 'period_key')
            ->map(static fn ($count): int => (int) $count)
            ->all();
    }

    /**
     * @return array{
     *   total_books:int,
     *   total_registered_cards:int,
     *   active_borrowers:int,
     *   books_on_loan:int,
     *   lost_books:int,
     *   overdue_loans:int,
     *   today_borrowed:int,
     *   digital_books_purchased:int,
     *   digital_revenue_vnd:int
     * }
     */
    private function buildSummary(): array
    {
        $openStatuses = [LoanStatus::BORROWED, LoanStatus::OVERDUE];

        return [
            'total_books' => (int) Book::query()->sum('quantity'),
            'total_registered_cards' => (int) LibraryCard::query()->count(),
            'active_borrowers' => (int) Loan::query()
                ->whereIn('status', $openStatuses)
                ->distinct()
                ->count('library_card_id'),
            'books_on_loan' => (int) LoanItem::query()
                ->join('loans', 'loan_items.loan_id', '=', 'loans.id')
                ->where('loans.deleted', false)
                ->whereIn('loans.status', $openStatuses)
                ->sum('loan_items.quantity'),
            'lost_books' => (int) LoanItem::query()
                ->join('loans', 'loan_items.loan_id', '=', 'loans.id')
                ->where('loans.deleted', false)
                ->where('loans.status', LoanStatus::RETURNED)
                ->where('loan_items.condition_on_return', LoanItemCondition::LOST->value)
                ->sum('loan_items.quantity'),
            'overdue_loans' => (int) Loan::query()
                ->where('status', LoanStatus::OVERDUE)
                ->count(),
            'today_borrowed' => (int) Loan::query()
                ->whereDate('loan_date', now()->toDateString())
                ->count(),
            'digital_books_purchased' => (int) OrderItem::query()
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.type', Order::TYPE_DIGITAL_PURCHASE)
                ->where('orders.status', Order::STATUS_PAID)
                ->sum('order_items.quantity'),
            'digital_revenue_vnd' => (int) Order::query()
                ->where('type', Order::TYPE_DIGITAL_PURCHASE)
                ->where('status', Order::STATUS_PAID)
                ->sum('total_vnd_snapshot'),
        ];
    }

    private function normalizeGranularity(string $granularity): string
    {
        return in_array($granularity, ['day', 'month', 'year'], true) ? $granularity : 'month';
    }

    private function loanPeriodExpression(string $column, string $granularity): string
    {
        return match ($granularity) {
            'day' => "DATE({$column})",
            'year' => "DATE_FORMAT({$column}, '%Y')",
            default => "DATE_FORMAT({$column}, '%Y-%m')",
        };
    }

    private function orderPaidPeriodExpression(string $ordersAlias, string $granularity): string
    {
        $paidAt = "{$ordersAlias}.paid_at";
        $updatedAt = "{$ordersAlias}.updated_at";
        $createdAt = "{$ordersAlias}.created_at";

        return match ($granularity) {
            'day' => "DATE(COALESCE({$paidAt}, {$updatedAt}, {$createdAt}))",
            'year' => "DATE_FORMAT(COALESCE({$paidAt}, {$updatedAt}, {$createdAt}), '%Y')",
            default => "DATE_FORMAT(COALESCE({$paidAt}, {$updatedAt}, {$createdAt}), '%Y-%m')",
        };
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
