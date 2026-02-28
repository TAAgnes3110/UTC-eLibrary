<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $activeLoans = Loan::where('user_id', $user->id)->where('status', 'active')->count();
        $overdueCount = Loan::where('user_id', $user->id)->where('status', 'active')->where('due_date', '<', now()->toDateString())->count();
        $hasCard = $user->libraryCard()->exists();

        return Inertia::render('Reader/Dashboard', [
            'stats' => [
                'activeLoans' => $activeLoans,
                'overdueCount' => $overdueCount,
                'hasCard' => $hasCard,
            ],
        ]);
    }
}
