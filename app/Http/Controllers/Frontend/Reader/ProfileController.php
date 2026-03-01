<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    use DecodesBackendResponse;

    public function edit(Request $request): Response
    {
        $response = app(ProfileController::class)->show($request);
        $data = $this->backendData($response);

        return Inertia::render('Reader/Profile/Edit', [
            'user' => $data,
        ]);
    }
}
