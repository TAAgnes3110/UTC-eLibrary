<?php

namespace App\Http\Controllers\Frontend\Reader;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function intro(): Response
    {
        $content = Cache::get('library_intro') ?? config('library.intro') ?? '';
        return Inertia::render('Reader/Intro', ['content' => $content]);
    }

    public function rules(): Response
    {
        $content = Cache::get('library_rules') ?? config('library.rules') ?? '';
        return Inertia::render('Reader/Rules', ['content' => $content]);
    }

    public function saved(): Response
    {
        return Inertia::render('Reader/Saved/Index');
    }
}
