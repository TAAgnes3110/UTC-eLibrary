<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Api\ReaderPageController;
use App\Http\Controllers\Frontend\Concerns\DecodesBackendResponse;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

/** Chỉ render trang. Nội dung intro/rules từ Backend API. */
class PageController extends Controller
{
    use DecodesBackendResponse;

    public function intro(): Response
    {
        $response = app(ReaderPageController::class)->introContent();
        $data = $this->backendData($response);
        return Inertia::render('Reader/Intro', ['content' => $data['content'] ?? '']);
    }

    public function rules(): Response
    {
        $response = app(ReaderPageController::class)->rulesContent();
        $data = $this->backendData($response);
        return Inertia::render('Reader/Rules', ['content' => $data['content'] ?? '']);
    }

    public function saved(): Response
    {
        return Inertia::render('Reader/Saved/Index');
    }
}
