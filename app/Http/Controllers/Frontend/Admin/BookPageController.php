<?php

namespace App\Http\Controllers\Frontend\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class BookPageController extends Controller
{
    public function index(): Response
    {
        return $this->printed();
    }

    public function printed(): Response
    {
        return Inertia::render('Admin/Books/Index', [
            'pageKind' => 'printed',
            'resourceTypeFilter' => 'textbook,reference',
        ]);
    }

    public function textbook(): Response
    {
        return $this->printed();
    }

    public function reference(): Response
    {
        return $this->printed();
    }

    public function digital(): Response
    {
        return Inertia::render('Admin/Books/Index', [
            'pageKind' => 'digital',
            'resourceTypeFilter' => 'digital',
        ]);
    }

    /** Duyệt yêu cầu tài liệu số độc giả gửi (upload mới chờ duyệt). */
    public function digitalSubmissions(): Response
    {
        return Inertia::render('Admin/Books/DigitalSubmissions');
    }
}
