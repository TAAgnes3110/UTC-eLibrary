<?php

namespace Tests\Unit\Services;

use App\Models\Period;
use App\Services\PeriodService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PeriodServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_sync_creates_nothing_when_no_periods_yet(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 8, 15, 0, 0, 0, 'UTC'));
        $n = app(PeriodService::class)->syncDueCohorts();
        $this->assertSame(0, $n);
    }

    public function test_sync_skips_next_cohort_before_august_first(): void
    {
        config(['app.timezone' => 'Asia/Ho_Chi_Minh']);
        Period::query()->create(PeriodService::buildAttributesForStartYear(2025));

        Carbon::setTestNow(Carbon::create(2026, 7, 31, 23, 59, 59, 'Asia/Ho_Chi_Minh'));

        $n = app(PeriodService::class)->syncDueCohorts();
        $this->assertSame(0, $n);
        $this->assertDatabaseMissing('periods', ['code' => 'NK2026']);
    }

    public function test_sync_adds_next_cohort_from_august_first(): void
    {
        config(['app.timezone' => 'Asia/Ho_Chi_Minh']);
        Period::query()->create(PeriodService::buildAttributesForStartYear(2025));

        Carbon::setTestNow(Carbon::create(2026, 8, 1, 0, 0, 0, 'Asia/Ho_Chi_Minh'));

        $n = app(PeriodService::class)->syncDueCohorts();
        $this->assertSame(1, $n);
        $this->assertDatabaseHas('periods', [
            'code' => 'NK2026',
            'start_year' => 2026,
            'end_year' => 2030,
        ]);
    }

    public function test_build_attributes_matches_k63_example(): void
    {
        $a = PeriodService::buildAttributesForStartYear(2022);
        $this->assertSame('NK2022', $a['code']);
        $this->assertSame('K63 (2022 - 2026)', $a['name']);
        $this->assertSame(2022, $a['start_year']);
        $this->assertSame(2026, $a['end_year']);
    }
}
