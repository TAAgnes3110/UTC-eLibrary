<?php

namespace App\Observers;

use App\Services\TaxonomyCacheService;

class TaxonomyCacheObserver
{
    public function saved(mixed $model): void
    {
        app(TaxonomyCacheService::class)->clear();
    }

    public function deleted(mixed $model): void
    {
        app(TaxonomyCacheService::class)->clear();
    }
}
