<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faculty;
use App\Models\Period;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class LibraryCardPageController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/LibraryCards/Index', $this->sharedProps());
    }

    public function requests(): Response
    {
        return Inertia::render('Admin/LibraryCards/Requests', $this->sharedProps());
    }

    public function counter(): Response
    {
        return Inertia::render('Admin/LibraryCards/CounterIssue', $this->sharedProps());
    }

    /**
     * @return array{faculties: Collection, periods: Collection}
     */
    private function sharedProps(): array
    {
        return [
            'faculties' => Faculty::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
            'periods' => Period::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'code', 'name', 'start_year', 'end_year']),
        ];
    }
}
