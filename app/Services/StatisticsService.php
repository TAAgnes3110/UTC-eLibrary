<?php

namespace App\Services;

use App\Enums\LoanStatus;
use App\Enums\LoanItemCondition;
use App\Models\Book;
use App\Models\LibraryCard;
use App\Models\Loan;
use App\Models\LoanItem;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;

class StatisticsService
{
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
        $loanGranularity = $this->normalizeGranularity($loanGranularity);
        $digitalGranularity = $this->normalizeGranularity($digitalGranularity ?? $loanGranularity);

        $series = $this->buildLoanSeries($loanGranularity);

        return [
            'granularity' => $loanGranularity,
            'digital_granularity' => $digitalGranularity,
            'summary' => $this->buildSummary(),
            'series' => $series,
            'digital_series' => $this->buildDigitalSeries($digitalGranularity),
            'forecast' => $this->forecastNextBorrow($series, $loanGranularity),
        ];
    }

    /**
     * @return list<array{key:string,label:string,borrowed:int,returned:int}>
     */
    private function buildLoanSeries(string $granularity): array
    {
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

        return $series;
    }

    /**
     * @return list<array{key:string,label:string,books_sold:int,revenue_vnd:int}>
     */
    private function buildDigitalSeries(string $granularity): array
    {
        [$periods, $startAt, $endAtExclusive] = $this->buildChartPeriods($granularity);

        $booksBuckets = [];
        $revenueBuckets = [];
        foreach ($periods as $period) {
            $booksBuckets[$period['key']] = 0;
            $revenueBuckets[$period['key']] = 0;
        }

        $paidOrders = Order::query()
            ->where('type', Order::TYPE_DIGITAL_PURCHASE)
            ->where('status', Order::STATUS_PAID)
            ->whereRaw(
                'DATE(COALESCE(paid_at, updated_at, created_at)) >= ?',
                [$startAt->toDateString()]
            )
            ->whereRaw(
                'DATE(COALESCE(paid_at, updated_at, created_at)) < ?',
                [$endAtExclusive->toDateString()]
            )
            ->get(['paid_at', 'updated_at', 'created_at', 'total_vnd_snapshot']);

        foreach ($paidOrders as $order) {
            $paidAt = $this->resolveOrderPaidAt($order);
            if ($paidAt === null) {
                continue;
            }
            $key = $this->formatPeriodKey($paidAt, $granularity);
            if (array_key_exists($key, $revenueBuckets)) {
                $revenueBuckets[$key] += (int) $order->total_vnd_snapshot;
            }
        }

        $paidOrderItems = OrderItem::query()
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.type', Order::TYPE_DIGITAL_PURCHASE)
            ->where('orders.status', Order::STATUS_PAID)
            ->whereRaw(
                'DATE(COALESCE(orders.paid_at, orders.updated_at, orders.created_at)) >= ?',
                [$startAt->toDateString()]
            )
            ->whereRaw(
                'DATE(COALESCE(orders.paid_at, orders.updated_at, orders.created_at)) < ?',
                [$endAtExclusive->toDateString()]
            )
            ->get([
                'order_items.quantity',
                'orders.paid_at as order_paid_at',
                'orders.updated_at as order_updated_at',
                'orders.created_at as order_created_at',
            ]);

        foreach ($paidOrderItems as $item) {
            $paidAt = $this->resolvePaidAtFromColumns(
                $item->order_paid_at,
                $item->order_updated_at,
                $item->order_created_at
            );
            if ($paidAt === null) {
                continue;
            }
            $key = $this->formatPeriodKey($paidAt, $granularity);
            if (array_key_exists($key, $booksBuckets)) {
                $booksBuckets[$key] += (int) $item->quantity;
            }
        }

        $digitalSeries = [];
        foreach ($periods as $period) {
            $digitalSeries[] = [
                'key' => $period['key'],
                'label' => $period['label'],
                'books_sold' => $booksBuckets[$period['key']] ?? 0,
                'revenue_vnd' => $revenueBuckets[$period['key']] ?? 0,
            ];
        }

        return $digitalSeries;
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

    private function resolveOrderPaidAt(Order $order): ?CarbonImmutable
    {
        return $this->resolvePaidAtFromColumns($order->paid_at, $order->updated_at, $order->created_at);
    }

    /**
     * Ngày ghi nhận thanh toán: paid_at → updated_at → created_at.
     */
    private function resolvePaidAtFromColumns(mixed $paidAt, mixed $updatedAt, mixed $createdAt = null): ?CarbonImmutable
    {
        $raw = $paidAt ?? $updatedAt ?? $createdAt ?? null;
        if ($raw === null) {
            return null;
        }

        return CarbonImmutable::parse($raw);
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
