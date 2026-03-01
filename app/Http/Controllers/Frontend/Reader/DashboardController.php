<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Api\ReaderController;
use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use DecodesBackendResponse;

    public function __invoke(Request $request): Response
    {
        $response = app(ReaderController::class)->dashboardData($request);
        $data = $this->backendData($response);

        return Inertia::render('Reader/Dashboard', [
            'stats' => $data['stats'] ?? ['activeLoans' => 0, 'overdueCount' => 0, 'hasCard' => false],
        ]);
    }
}
