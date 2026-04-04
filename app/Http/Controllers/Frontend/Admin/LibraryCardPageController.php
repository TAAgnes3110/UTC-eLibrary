<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Models\Period;
use Inertia\Inertia;
use Inertia\Response;

class LibraryCardPageController extends Controller
{
    /**
     * @return list<array{id: int, code: string, name: string, start_year: int|null, end_year: int|null}>
     */
    private function periodsForInertia(): array
    {
        return Period::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'code', 'name', 'start_year', 'end_year'])
            ->map(fn (Period $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'start_year' => $p->start_year,
                'end_year' => $p->end_year,
            ])
            ->all();
    }

    public function manage(): Response
    {
        return Inertia::render('Admin/LibraryCards/Index', [
            'section' => 'manage',
            'pageTitle' => 'Quản lý thẻ thư viện',
            'periods' => $this->periodsForInertia(),
        ]);
    }

    public function approve(): Response
    {
        return Inertia::render('Admin/LibraryCards/Index', [
            'section' => 'approve',
            'pageTitle' => 'Duyệt yêu cầu cấp thẻ',
            'periods' => $this->periodsForInertia(),
        ]);
    }

    public function quick(): Response
    {
        return Inertia::render('Admin/LibraryCards/QuickIssue', [
            'pageTitle' => 'Cấp thẻ thư viện nhanh',
            'periods' => $this->periodsForInertia(),
        ]);
    }
}
