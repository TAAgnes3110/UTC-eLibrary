<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Controller;
use App\Models\CmsPage;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function intro(): Response
    {
        $page = CmsPage::where('slug', 'gioi-thieu-thu-vien-utc')
            ->where('is_published', true)
            ->first();

        return Inertia::render('Reader/Intro', [
            'content' => $page?->content ?? '',
        ]);
    }

    public function rules(): Response
    {
        $page = CmsPage::where('slug', 'quy-dinh-su-dung-thu-vien')
            ->where('is_published', true)
            ->first();

        return Inertia::render('Reader/Rules', [
            'content' => $page?->content ?? '',
        ]);
    }

    public function saved(): Response
    {
        return Inertia::render('Reader/Saved/Index');
    }
}
