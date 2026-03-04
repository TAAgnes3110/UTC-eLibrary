<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileChangeRequestController extends Controller
{
    use DecodesBackendResponse;

    public function index(Request $request): Response
    {
        $response = app(\App\Http\Controllers\Api\ProfileChangeRequestController::class)->pageData($request);
        $data = $this->backendData($response);

        return Inertia::render('Reader/ProfileChangeRequest/Index', [
            'user' => $data['user'] ?? [],
            'faculties' => $data['faculties'] ?? [],
            'departments' => $data['departments'] ?? [],
            'cohorts' => $data['cohorts'] ?? [],
            'myRequests' => $data['myRequests'] ?? [],
        ]);
    }
}
