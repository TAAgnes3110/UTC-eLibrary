<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ReaderPageService
{
    public function introContent(): string
    {
        return Cache::get('library_intro') ?? config('library.intro') ?? '';
    }

    public function rulesContent(): string
    {
        return Cache::get('library_rules') ?? config('library.rules') ?? '';
    }
}
